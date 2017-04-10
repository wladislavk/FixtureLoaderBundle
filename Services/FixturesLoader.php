<?php
namespace VKR\FixtureLoaderBundle\Services;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VKR\FixtureLoaderBundle\Decorators\DataFixturesLoaderDecorator;
use VKR\FixtureLoaderBundle\Decorators\FilesystemDecorator;
use VKR\FixtureLoaderBundle\Exception\FixtureLoaderException;

class FixturesLoader
{
    const PATH_TO_ROOT = __DIR__ . '/../../../..';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ContainerAwareLoader
     */
    private $dataFixturesLoader;

    /**
     * @var ORMExecutor
     */
    private $ormExecutor;

    /**
     * @var ORMPurger
     */
    private $ormPurger;

    /**
     * @var FilesystemDecorator
     */
    private $filesystemDecorator;

    /**
     * @var DataFixturesLoaderDecorator
     */
    private $dataFixturesLoaderDecorator;

    /**
     * @var string
     */
    private $rootFixturesDir;

    public function __construct(
        EntityManager $entityManager,
        ContainerAwareLoader $dataFixturesLoader,
        ORMExecutor $ormExecutor,
        ORMPurger $ormPurger,
        FilesystemDecorator $filesystemDecorator,
        DataFixturesLoaderDecorator $dataFixturesLoaderDecorator,
        $rootFixturesDir
    ) {
        $this->entityManager = $entityManager;
        $this->dataFixturesLoader = $dataFixturesLoader;
        $this->ormExecutor = $ormExecutor;
        $this->ormPurger = $ormPurger;
        $this->filesystemDecorator = $filesystemDecorator;
        $this->dataFixturesLoaderDecorator = $dataFixturesLoaderDecorator;
        $this->rootFixturesDir = $rootFixturesDir;
    }

    /**
     * @param string $fixturesType
     * @param callable $loggingFunction
     * @param bool $append
     * @throws FixtureLoaderException
     */
    public function loadFixtures($fixturesType, $loggingFunction, $append)
    {
        $this->checkIfMySQL();
        if (!is_callable($loggingFunction)) {
            throw new FixtureLoaderException("Logging function is not a valid callable");
        }
        $path = $this->setPath($fixturesType);
        $fixtures = $this->getFixtures($path);
        $connection = $this->entityManager->getConnection();
        $this->setForeignKeyChecks($connection, false);
        try {
            $this->ormPurger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
            $this->ormExecutor->setLogger($loggingFunction);
            $this->ormExecutor->execute($fixtures, $append);
        } catch (\Exception $e) {
            throw new FixtureLoaderException($e->getMessage(), $e->getCode());
        } finally {
            $this->setForeignKeyChecks($connection, true);
            $connection->close();
        }
    }

    /**
     * @throws FixtureLoaderException
     */
    private function checkIfMySQL()
    {
        $dbEngine = $this->entityManager->getConnection()
            ->getDatabasePlatform()->getName();
        // different versions of MySQL will return different names
        if (!strstr(strtolower($dbEngine), 'mysql')) {
            throw new FixtureLoaderException('FixtureLoaderBundle works only with MySQL connections');
        }
    }

    /**
     * @param string $fixturesType
     * @return string
     */
    private function setPath($fixturesType)
    {
        $path = self::PATH_TO_ROOT . $this->rootFixturesDir . '/' . $fixturesType;
        return $path;
    }

    /**
     * @param string $path
     * @return array
     */
    private function getFixtures($path)
    {
        if (!$this->filesystemDecorator->isDir($path)) {
            return [];
        }
        if (sizeof($this->dataFixturesLoader->getFixtures())) {
            $this->dataFixturesLoader = $this->dataFixturesLoaderDecorator->getNewLoader();
        }
        $this->dataFixturesLoader->loadFromDirectory($path);
        return $this->dataFixturesLoader->getFixtures();
    }

    /**
     * @param Connection $connection
     * @param bool $value
     */
    private function setForeignKeyChecks(Connection $connection, $value)
    {
        $value = intval(boolval($value));
        $connection->executeQuery("SET FOREIGN_KEY_CHECKS = $value;");
    }
}
