Overview
========

This bundle simplifies loading of fixtures for applications that use Doctrine Fixtures and MySQL.
It introduces sensible defaults and provides a couple of console commands that replace
```doctrine:fixtures:load``` and provides an opportunity to write your own commands and rulesets. 
It also deals with an old and well-known bug that concerns truncating the DB that contains 
foreign keys.

Please note that this bundle contains some non-ANSI-compatible MySQL code and therefore
cannot be used with other DB drivers supported by Doctrine DBAL.

Configuration
=============

This bundle accepts two configuration parameters, both of which are optional and located
under ```vkr_fixture_loader``` key.

```fixture_directory``` sets the path relative to Symfony root directory for the parent
directory to where your fixture directories are located. For example, if you have
```InitialData``` and ```TestData``` directories as children to ```/symfony/path/fixtures```,
then this setting should be set to ```/fixtures```. The default value is ```/src/AppBundle/DataFixtures/ORM```.

```fixture_type_manager``` setting needs to be used if you create your own type manager.
It should be equal to service alias as set in ```services.yml```. The default value is
```vkr_fixture_loader.fixture_type_manager```.

Composition
===========

FixturesLoader
--------------

At the heart of this bundle lies a service called ```FixturesLoader```. Its only public
method ```loadFixtures()``` accepts three arguments. The first argument corresponds to
the directory name of the fixture subset relative to ```fixture_directory``` setting.
The second argument is a callable that is responsible for displaying the logging information
to the console. This callable can be set in the ```execute()``` method of your command class
and should use ```OutputInterface``` variable of Symfony Console component. The third
argument is a boolean that purges the database (not the table!) if false or keeps the existing data
if true.

Unlike the default Doctrine Fixtures load command, this service is opinionated in the 
following manner:

1) If the third argument is false, the data is always purged, not deleted, that allows
client coders to rely upon auto-incremented columns in fixtures.
2) Foreign key checks are always disabled while purging to suppress error messages
in younger versions of MySQL.

If the directory specified as the first argument is empty or does not exist, no errors
will be thrown and, given that the third argument is false, the data will be purged
anyway.

FixtureTypeManager
------------------

This simple class defines the ruleset for various console fixture loaders. The
default version of this class defines three public methods.

```setLoggingFunction()``` accepts a callable as its argument and passes it on to
```FixturesLoader```. This method must be called before any other method.

```loadInitialData()``` handles ```vkr:fixtures:initial``` command, it loads fixtures
from ```InitialData``` directory and purges the DB if ```--purge``` option is set.

```restoreTestDB()``` handles ```vkr:fixtures:test``` command. It does the following:
1) purges the DB
2) loads fixtures from ```InitialData``` directory if they exist
3) loads fixtures from ```TestData directory```.

This class can be extended to include your own commands and rulesets.

Console commands
----------------

Two commands are shipped with this bundle.

```vkr:fixtures:initial``` is designed to set static data that is needed for the
 application to work and is unlikely to change in the future, such as list of supported languages.
 It loads fixtures from ```InitialData``` directory and it will purge the DB if called with ```--purge```
 option (meaning that it will not purge anything by default). It is important to understand
 that if this option is not set, target tables will not be purged, which can lead to
 unexpected behavior. It is recommended to truncate the data from target tables manually in
 your fixture classes.

```vkr:fixtures:test``` is designed for restoring the test database data for functional tests.
It purges the database, then tries to load ```InitialData``` fixtures (that allows to
avoid the overhead of running ```vkr:fixtures:initial```), then loads fixtures
from ```TestData``` directory. In order to be foolproof, this command will error out if used
without ```--env=test``` option. It allows to define ```parameters_test.yml``` file
with its own connection data that will be used for purgeable test DB while keeping
the main DB safe. If one test environment is not enough for you, you can create additional
environments that contain the word ```test``` in their names and use this command in this
manner: ```vkr:fixtures:test --env=test2```.

Both commands can work with non-default entity managers by providing ```--em``` option.

Customization
=============

Custom type managers
--------------------

If you want to define your own fixture-loading commands or override the behavior
of built-in commands, you can define your own classes that extend ```FixtureTypeManager```
and add public methods or override existing methods. It is recommended to call
```$this->loadFixtures()``` from all new methods.

Custom commands
---------------

You can add your own console commands that will use ```FixtureTypeManager``` and
```FixturesLoader```. The easiest way to do this is to copy one of the built-in
commands and modify it. It is recommended to include these lines in your ```execute()```
method:

```
$managerServiceName = $this->getContainer()->getParameter(Constants::FIXTURE_MANAGER_PARAM);
/** @var FixtureTypeManager $fixtureTypeManager */
$fixtureTypeManager = $this->getContainer()->get($managerServiceName);
if (!$fixtureTypeManager instanceof FixtureTypeManager) {
    throw new FixtureLoaderException('Fixture type manager service must extend ' . FixtureTypeManager::class);
}
...
$fixtureTypeManager->setLoggingFunction($myLoggingFunction);
$fixtureTypeManager->myPublicMethod();
return null;
```
