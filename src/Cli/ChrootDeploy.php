<?php

namespace Upsalter\Cli;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Upsalter\DistributionManager;

class ChrootDeploy extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('chroot:deploy')
            // the short description shown while running "php bin/console list"
            ->setDescription('Deploys new chroot package (tar.gz)')
            ->addArgument('package', InputArgument::REQUIRED, 'Which distribution to build')
            ->addArgument('user', InputArgument::REQUIRED, 'Server to deploy to (user@server:containerdirectory)')
            ->addArgument('server', InputArgument::REQUIRED, 'Server to deploy to (user@server:containerdirectory)')
            ->addArgument('location', InputArgument::REQUIRED, 'Directory storing containers')
            ->addArgument('master', InputArgument::REQUIRED, 'Salt master')
            ->addArgument('id', InputArgument::REQUIRED, 'Minion id')
            ->addOption('no-register-key','R',InputOption::VALUE_NONE,'Disable register trough local salt-key')
            ->addOption('skip-start','S',InputOption::VALUE_NONE,'Skip startup of minion (implies -R)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $package = $input->getArgument('package');
        $user = $input->getArgument('user');
        $server = $input->getArgument('server');
        $location = $input->getArgument('location');
        $master = $input->getArgument('master');
        $minionId = $input->getArgument('id');
        $skipRegister = $input->getOption('no-register-key');
        $skipStart = $skipRegister ? $skipRegister : $input->getOption('skip-start');

        if(!file_exists($input->getArgument('package'))) {
            throw new \Exception('Package not found');
        }
        $output->writeln($package.' file found, checking SSH accesses ...');

        $cmd = '[ -d '.escapeshellarg($location).' ]';
        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('SSH connection to '.$server.' failed!');
        }
        $output->writeln('SSH access to '.$server.' OK, container directory present. Deploying ...');

        $finalLocation = $location.DIRECTORY_SEPARATOR.$minionId;

        $cmd = '[ ! -d '.escapeshellarg($finalLocation).' ] && mkdir '.escapeshellarg($finalLocation);
        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Containers '.$minionId.' already deployed, skipping');
        }
        $cmd = 'tar -xvjC '.escapeshellarg($finalLocation);
        $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd).' < '.escapeshellarg($package);
        exec($cmd,$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Containers '.$minionId.' deploy failed');
        }

    }
}
