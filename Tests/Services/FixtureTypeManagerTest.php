<?php
namespace VKR\FixtureLoaderBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use VKR\FixtureLoaderBundle\Exception\FixtureLoaderException;
use VKR\FixtureLoaderBundle\Services\FixtureTypeManager;
use VKR\FixtureLoaderBundle\Services\FixturesLoader;

class FixtureTypeManagerTest extends TestCase
{
    private $loadedFixtures = [];

    /**
     * @var callable
     */
    private $loggingFunction;

    /**
     * @var FixtureTypeManager
     */
    private $fixtureTypeManager;

    public function setUp()
    {
        $this->loggingFunction = function () {
            return true;
        };

        $fixturesLoader = $this->mockFixturesLoader();
        $this->fixtureTypeManager = new FixtureTypeManager($fixturesLoader);
    }

    public function testLoadInitialData()
    {
        $this->fixtureTypeManager->setLoggingFunction($this->loggingFunction);
        $this->fixtureTypeManager->loadInitialData();
        $expected = [
            $this->fixtureTypeManager->initialDataType => true,
        ];
        $this->assertEquals($expected, $this->loadedFixtures);
    }

    public function testRestoreTestDB()
    {
        $this->fixtureTypeManager->setLoggingFunction($this->loggingFunction);
        $this->fixtureTypeManager->restoreTestDB();
        $expected = [
            $this->fixtureTypeManager->initialDataType => false,
            $this->fixtureTypeManager->testDataType => true,
        ];
        $this->assertEquals($expected, $this->loadedFixtures);
    }

    public function testWithUnsetLoggingFunction()
    {
        $this->expectException(FixtureLoaderException::class);
        $this->expectExceptionMessage('setLoggingFunction() must be called');
        $this->fixtureTypeManager->loadInitialData();
    }

    public function testWithLoggingFunctionThatIsNotValidCallable()
    {
        $this->loggingFunction = 'foo';
        $this->expectException(FixtureLoaderException::class);
        $this->expectExceptionMessage('Logging function must be a valid callable');
        $this->fixtureTypeManager->setLoggingFunction($this->loggingFunction);
    }

    private function mockFixturesLoader()
    {
        $fixturesLoader = $this->createMock(FixturesLoader::class);
        $fixturesLoader->method('loadFixtures')
            ->willReturnCallback([$this, 'loadFixturesCallback']);
        return $fixturesLoader;
    }

    public function loadFixturesCallback($type, $function, $append)
    {
        $this->loadedFixtures[$type] = $append;
    }
}
