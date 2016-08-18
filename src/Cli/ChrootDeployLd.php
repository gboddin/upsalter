<?php

namespace Upsalter\Cli;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Upsalter\DistributionManager;

class ChrootDeployLd extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('chroot:create-ld-user')
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates ld-user on server and register cron')
            ->addArgument('destination', InputArgument::REQUIRED, 'Server to deploy to (user@server:containerdirectory)')
            ->addOption('skip-start','S',InputOption::VALUE_NONE,'Skip startup of minion (implies -R)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!file_exists($input->getArgument('package'))) {
            throw new \Exception('Package not found');
        }
        exec('ssh -oBatchMode=yes /bin/true',$output,$rc);
        if($rc > 0) {
            throw new\Exception('Connection to ');
        }
    }
}
