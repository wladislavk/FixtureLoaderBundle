<?php
namespace VKR\FixtureLoaderBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use VKR\FixtureLoaderBundle\Constants;
use VKR\FixtureLoaderBundle\Exception\FixtureLoaderException;
use VKR\FixtureLoaderBundle\Services\FixtureTypeManager;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RestoreTestDBCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('vkr:fixtures:test')
            ->setDescription('Restore test database from fixtures')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     * @throws FixtureLoaderException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $application */
        $application = $this->getApplication();
        $this->checkEnv($application);
        DoctrineCommandHelper::setApplicationEntityManager($application, $input->getOption('em'));

        $managerServiceName = $this->getContainer()->getParameter(Constants::FIXTURE_MANAGER_PARAM);
        /** @var FixtureTypeManager $fixtureTypeManager */
        $fixtureTypeManager = $this->getContainer()->get($managerServiceName);
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');

        if (!$fixtureTypeManager instanceof FixtureTypeManager) {
            throw new FixtureLoaderException('Fixture type manager service must extend ' . FixtureTypeManager::class);
        }
        if ($input->isInteractive()) {
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
        $fixtureTypeManager->restoreTestDB();
        return null;
    }

    /**
     * @param Application $application
     * @throws \InvalidArgumentException
     */
    private function checkEnv(Application $application)
    {
        // Symfony does not allow to change default env at runtime. if the default test env will
        // not do, use a custom env that has 'test' in its name
        if (!strstr($application->getKernel()->getEnvironment(), 'test')) {
            throw new \InvalidArgumentException('This command should only be run in test environment');
        }
    }
}
