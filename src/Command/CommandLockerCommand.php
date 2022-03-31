<?php

namespace TorqIT\CommandLockerBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;

class CommandLockerCommand extends AbstractCommand
{
    private const ARG_COMMAND_WITH_OPTIONS = 'command-with-options';

    /**
     * @var LockFactory|null
     */
    private $lockFactory = null;

    public function __construct(LockFactory $lockFactory)
    {
        parent::__construct();
        $this->lockFactory = $lockFactory;
    }

    protected function configure()
    {
        $this->setName('torq:command-locker')
            ->setDescription('Run a command and ensure any other instances in deployment aren\'t running it at the same time!')
            ->addArgument(
                self::ARG_COMMAND_WITH_OPTIONS,
                InputArgument::REQUIRED,
                'Full command to run with all options'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fullCommand = $input->getArgument(self::ARG_COMMAND_WITH_OPTIONS);

        $commandName = explode(' ', $fullCommand)[0];

        $lockName = "command-locker-$commandName";

        $output->writeln("Getting lock for $lockName", OutputInterface::VERBOSITY_NORMAL);

        $lock = $this->lockFactory->createLock($lockName, 60 * 60 * 24);

        if (!$lock->acquire()) {
            $output->writeln("<error>Could not get lock for $lockName</error>", OutputInterface::VERBOSITY_NORMAL);

            return Command::FAILURE;
        }

        $application = $this->getApplication();

        try {
            $argvArr = $this->paramStringToArgv('bin/console ' . $fullCommand);
            $inputArgs = new ArgvInput($argvArr);
            $application->setAutoExit(false);
            $application->run($inputArgs, $output);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $output->writeln("<error>Error running command: $errorMsg</error>");
            return Command::FAILURE;
        } finally {
            $output->writeln("Releasing lock $lockName", OutputInterface::VERBOSITY_NORMAL);
            $lock->release();
        }

        $application->setAutoExit(true);

        return Command::SUCCESS;
    }

    /**
     * Thanks to: https://stackoverflow.com/questions/34868421/get-argv-from-a-string-with-php
     * @param string $str
     * @return array
     */
    private function paramStringToArgv(string $str): array
    {
        // the array shift removes the dash I had as first element of the argv, your mileage may vary
        $serializedArguments = shell_exec(
            sprintf('php -r "array_shift(\\$argv); echo serialize(\\$argv);" -- %s', $str)
        );
        return unserialize($serializedArguments);
    }
}
