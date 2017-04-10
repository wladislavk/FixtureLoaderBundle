<?php
namespace VKR\FixtureLoaderBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use VKR\FixtureLoaderBundle\Constants;
use VKR\FixtureLoaderBundle\Exception\FixtureLoaderException;
use VKR\FixtureLoaderBundle\Services\FixtureTypeManager;

class LoadInitialDataCommand extends DoctrineCommand
{
    const PURGE_OPTION_NAME = 'purge';

    protected function configure()
    {
        $this
            ->setName('vkr:fixtures:initial')
            ->setDescription('Loads initial data into the database')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
            ->addOption(self::PURGE_OPTION_NAME, null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $application */
        $application = $this->getApplication();
        DoctrineCommandHelper::setApplicationEntityManager($application, $input->getOption('em'));

        $managerServiceName = $this->getContainer()->getParameter(Constants::FIXTURE_MANAGER_PARAM);
        /** @var FixtureTypeManager $fixtureTypeManager */
        $fixtureTypeManager = $this->getContainer()->get($managerServiceName);
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');

        if (!$fixtureTypeManager instanceof FixtureTypeManager) {
            throw new FixtureLoaderException('Fixture type manager service must extend ' . FixtureTypeManager::class);
        }
        $append = !boolval($input->getOption(self::PURGE_OPTION_NAME));
        if ($input->isInteractive() && !$append) {
            $question = new ConfirmationQuestion(Constants::PURGE_QUESTION, false);
            $isConfirmed = $questionHelper->ask($input, $output, $question);
            if (!$isConfirmed) {
                return null;
            }
        }
        $loggingFunction = function ($message) use ($output) {
            $output->writeln(sprintf(Constants::LOGGING_OUTPUT, $message));
        };
        $fixtureTypeManager->setLoggingFunction($loggingFunction);
        $fixtureTypeManager->loadInitialData($append);
        return null;
    }
}
