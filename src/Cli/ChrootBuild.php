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
            ->setName('chroot:build')
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates new chroot package (tar.gz)')
            ->addArgument('distribution', InputArgument::REQUIRED, 'Which distribution to build')
            ->addArgument('version', InputArgument::REQUIRED, 'Which distribution version to build')
            ->addArgument('target',InputArgument::REQUIRED,'Target tarball');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $distroManager = new DistributionManager();
        $distributionName = $input->getArgument('distribution');
        $distributionVersion = $input->getArgument('version');

        $distro = $distroManager->getDistro($distributionName,$distributionVersion);

        $tempdir = $distro->getTempDir();
        $output->writeln('Downloading ROOTFS in '.$tempdir);
        $distro->buildRoot($tempdir);
        rename($tempdir,$input->getArgument('target'));

    }
}