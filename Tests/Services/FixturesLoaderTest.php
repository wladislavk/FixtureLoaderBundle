<?php
namespace VKR\FixtureLoaderBundle\Tests\Services;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use VKR\FixtureLoaderBundle\Decorators\DataFixturesLoaderDecorator;
use VKR\FixtureLoaderBundle\Decorators\FilesystemDecorator;
use VKR\FixtureLoaderBundle\Exception\FixtureLoaderException;
use VKR\FixtureLoaderBundle\Services\FixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

class FixturesLoaderTest extends TestCase
{
    const FIXTURES_ROOT_DIR = '/src/AppBundle/Fixtures';

    private $fixtures = [];

    private $loadedFixtures = [];

    private $loadedPath;

    private $executedFixtures = [];

    private $executedQueries = [];

    private $purgeMode;

    private $loggingFunction;

    private $ormLogger;

    private $databaseName;

    private $isDir = true;

    private $shouldThrowException = false;

    private $isConnectionOpen = false;

    private $loadersRequested = 0;

    /**
     * @var FixturesLoader
     */
    private $fixturesLoader;

    public function setUp()
    {
        $this->databaseName = 'MySQL';
        $this->loggingFunction = function () {
            return true;
        };

        $entityManager = $this->mockEntityManager();
        $containerAwareLoader = $this->mockContainerAwareLoader();
        $ormExecutor = $this->mockOrmExecutor();
        $ormPurger = $this->mockOrmPurger();
        $filesystemDecorator = $this->mockFilesystemDecorator();
        $dataFixturesLoaderDecorator = $this->mockDataFixturesLoaderDecorator();
        $this->fixturesLoader = new FixturesLoader(
            $entityManager,
            $containerAwareLoader,
            $ormExecutor,
            $ormPurger,
            $filesystemDecorator,
            $dataFixturesLoaderDecorator,
            self::FIXTURES_ROOT_DIR
        );
    }

    public function testLoadFixturesWithoutExisting()
    {
        $this->fixtures = ['Departments', 'Employees'];
        $this->fixturesLoader->loadFixtures('test', $this->loggingFunction, true);
        $this->assertEquals(ORMPurger::PURGE_MODE_TRUNCATE, $this->purgeMode);
        $this->assertEquals($this->loggingFunction, $this->ormLogger);
        $this->assertEquals(2, sizeof($this->executedQueries));
        $this->assertEquals('SET FOREIGN_KEY_CHECKS = 0;', $this->executedQueries[0]);
        $this->assertEquals('SET FOREIGN_KEY_CHECKS = 1;', $this->executedQueries[1]);
        $this->assertFalse($this->isConnectionOpen);
        $executedFixtures = [
            'Departments' => true,
            'Employees' => true,
        ];
        $this->assertEquals($executedFixtures, $this->executedFixtures);
        $this->assertEquals(FixturesLoader::PATH_TO_ROOT . self::FIXTURES_ROOT_DIR . '/test', $this->loadedPath);
        $this->assertEquals(0, $this->loadersRequested);
    }

    public function testLoadFixturesWithExisting()
    {
        $this->fixtures = ['Departments', 'Employees'];
        $this->loadedFixtures = ['Departments'];
        $this->fixturesLoader->loadFixtures('test', $this->loggingFunction, true);
        $executedFixtures = [
            'Departments' => true,
            'Employees' => true,
        ];
        $this->assertEquals($executedFixtures, $this->executedFixtures);
        $this->assertEquals(1, $this->loadersRequested);
    }

    public function testLoadWithNonexistentPath()
    {
        $this->isDir = false;
        $this->fixtures = ['PlusDepartments', 'PlusEmployees'];
        $this->fixturesLoader->loadFixtures('foo', $this->loggingFunction, true);
        $this->assertEquals([], $this->executedFixtures);
    }

    public function testLoadWithNonValidCallable()
    {
        $this->loggingFunction = 'foo';
        $this->fixtures = ['PlusDepartments', 'PlusEmployees'];
        $this->expectException(FixtureLoaderException::class);
        $this->expectExceptionMessage("Logging function is not a valid callable");
        $this->fixturesLoader->loadFixtures('test', $this->loggingFunction, true);
    }

    public function testWithNonMySQL()
    {
        $this->databaseName = 'foo';
        $this->fixtures = ['PlusDepartments', 'PlusEmployees'];
        $this->expectException(FixtureLoaderException::class);
        $this->expectExceptionMessage('FixtureLoaderBundle works only with MySQL connections');
        $this->fixturesLoader->loadFixtures('test', $this->loggingFunction, true);
    }

    public function testWithErrorWhileExecuting()
    {
        $this->shouldThrowException = true;
        $this->fixtures = ['PlusDepartments', 'PlusEmployees'];
        $this->expectException(FixtureLoaderException::class);
        $this->expectExceptionMessage('Foo');
        $this->expectExceptionCode(400);
        $this->fixturesLoader->loadFixtures('foo', $this->loggingFunction, true);
        $this->assertEquals('SET FOREIGN_KEY_CHECKS = 0;', $this->executedQueries[0]);
        $this->assertEquals('SET FOREIGN_KEY_CHECKS = 1;', $this->executedQueries[1]);
        $this->assertFalse($this->isConnectionOpen);
    }

    private function mockEntityManager()
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getConnection')->willReturn($this->mockConnection());
        return $entityManager;
    }

    private function mockConnection()
    {
        $this->isConnectionOpen = true;
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')
            ->willReturnCallback([$this, 'executeQueryCallback']);
        $connection->method('getDatabasePlatform')->willReturn($this->mockDatabasePlatform());
        $connection->method('close')->willReturnCallback([$this, 'closeConnectionCallback']);
        return $connection;
    }

    private function mockDatabasePlatform()
    {
        $databasePlatform = $this->createMock(AbstractPlatform::class);
        $databasePlatform->method('getName')
            ->willReturnCallback([$this, 'getDatabaseNameCallback']);
        return $databasePlatform;
    }

    private function mockContainerAwareLoader()
    {
        $containerAwareLoader = $this->createMock(ContainerAwareLoader::class);
        $containerAwareLoader->method('loadFromDirectory')
            ->willReturnCallback([$this, 'loadFromDirectoryCallback']);
        $containerAwareLoader->method('getFixtures')
            ->willReturnCallback([$this, 'getFixturesCallback']);
        return $containerAwareLoader;
    }

    private function mockOrmExecutor()
    {
        $ormExecutor = $this->createMock(ORMExecutor::class);
        $ormExecutor->method('setLogger')
            ->willReturnCallback([$this, 'setLoggerCallback']);
        $ormExecutor->method('execute')
            ->willReturnCallback([$this, 'executeCallback']);
        return $ormExecutor;
    }

    private function mockOrmPurger()
    {
        $ormPurger = $this->createMock(ORMPurger::class);
        $ormPurger->method('setPurgeMode')
            ->willReturnCallback([$this, 'setPurgeModeCallback']);
        return $ormPurger;
    }

    private function mockFilesystemDecorator()
    {
        $filesystemDecorator = $this->createMock(FilesystemDecorator::class);
        $filesystemDecorator->method('isDir')->willReturnCallback([$this, 'isDirCallback']);
        return $filesystemDecorator;
    }

    private function mockDataFixturesLoaderDecorator()
    {
        $decorator = $this->createMock(DataFixturesLoaderDecorator::class);
        $decorator->method('getNewLoader')
            ->willReturnCallback([$this, 'getNewLoaderCallback']);
        return $decorator;
    }

    public function getNewLoaderCallback()
    {
        $this->loadersRequested++;
        return $this->mockContainerAwareLoader();
    }

    public function executeQueryCallback($query)
    {
        $this->executedQueries[] = $query;
    }

    public function getDatabaseNameCallback()
    {
        return $this->databaseName;
    }

    public function setPurgeModeCallback($mode)
    {
        $this->purgeMode = $mode;
    }

    public function loadFromDirectoryCallback($path)
    {
        $this->loadedFixtures = $this->fixtures;
        $this->loadedPath = $path;
    }

    public function getFixturesCallback()
    {
        $fixturesToReturn = $this->loadedFixtures;
        $this->loadedFixtures = [];
        return $fixturesToReturn;
    }

    public function setLoggerCallback($loggingFunction)
    {
        $this->ormLogger = $loggingFunction;
    }

    public function executeCallback($fixtures, $append)
    {
        if ($this->shouldThrowException) {
            throw new \Exception('Foo', 400);
        }
        foreach ($fixtures as $fixture) {
            $this->executedFixtures[$fixture] = $append;
        }
    }

    public function isDirCallback()
    {
        return $this->isDir;
    }

    public function closeConnectionCallback()
    {
        $this->isConnectionOpen = false;
    }
}
