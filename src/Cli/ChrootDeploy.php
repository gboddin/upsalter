<?php

namespace Upsalter\Cli;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Upsalter\DistributionManager;


class ChrootBuild extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('chroot:deploy')
            // the short description shown while running "php bin/console list"
            ->setDescription('Deploys new chroot package (tar.gz)')
            ->addArgument('package', InputArgument::REQUIRED, 'Which distribution to build')
            ->addArgument('destination', InputArgument::REQUIRED, 'Server to deploy to (user@server:containerdirectory)')
            ->addArgument('master',InputArgument::REQUIRED,'Salt master')
            ->addArgument('id',InputArgument::REQUIRED,'Minion id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}