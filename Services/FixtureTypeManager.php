<?php
namespace VKR\FixtureLoaderBundle\Services;

use VKR\FixtureLoaderBundle\Exception\FixtureLoaderException;

class FixtureTypeManager
{
    public $initialDataType = 'InitialData';

    public $testDataType = 'TestData';

    /**
     * @var FixturesLoader
     */
    private $fixturesLoader;

    /**
     * @var callable
     */
    protected $loggingFunction;

    public function __construct(FixturesLoader $fixturesLoader)
    {
        $this->fixturesLoader = $fixturesLoader;
    }

    /**
     * @param callback $loggingFunction
     * @throws FixtureLoaderException
     */
    public function setLoggingFunction($loggingFunction)
    {
        if (!is_callable($loggingFunction)) {
            throw new FixtureLoaderException('Logging function must be a valid callable');
        }
        $this->loggingFunction = $loggingFunction;
    }

    /**
     * @param bool $append
     */
    public function loadInitialData($append = true)
    {
        $this->loadFixtures($this->initialDataType, $append);
    }

    public function restoreTestDB()
    {
        $this->loadInitialData(false);
        $this->loadFixtures($this->testDataType, true);
    }

    /**
     * @param string $fixtureType
     * @param bool $append
     * @throws FixtureLoaderException
     */
    protected function loadFixtures($fixtureType, $append)
    {
        if (!is_callable($this->loggingFunction)) {
            throw new FixtureLoaderException('setLoggingFunction() must be called');
        }
        $this->fixturesLoader->loadFixtures($fixtureType, $this->loggingFunction, $append);
    }
}
