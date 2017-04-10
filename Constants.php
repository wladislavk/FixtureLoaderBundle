<?php
namespace VKR\FixtureLoaderBundle;

class Constants
{
    const DEFAULT_FIXTURE_DIR = '/src/AppBundle/DataFixtures/ORM';
    const PURGE_QUESTION = '<question>Careful, database will be purged. Do you want to continue y/N ?</question>';
    const LOGGING_OUTPUT = '  <comment>></comment> <info>%s</info>';
    const FIXTURE_DIRECTORY_PARAM = 'vkr_fixture_loader.fixture_directory';
    const FIXTURE_MANAGER_PARAM = 'vkr_fixture_loader.fixture_type_manager';
}
