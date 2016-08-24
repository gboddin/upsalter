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
            ->addArgument('user', InputArgument::REQUIRED, 'Remote user')
            ->addArgument('server', InputArgument::REQUIRED, 'Server to deploy to')
            ->addArgument('location', InputArgument::REQUIRED, 'Remote container directory')
            ->addOption('skip-start','S',InputOption::VALUE_NONE,'Skip startup of minion (implies -R)')
            ->addOption('skip-cron','C',InputOption::VALUE_NONE,'Skip adding the cron entry for reboot');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getArgument('user');
        $server = $input->getArgument('server');
        $location = $input->getArgument('location');
        $skipStart = $input->getOption('skip-start');
        $skipCron = $input->getOption('skip-cron');
        $cmd = '[ -x '.escapeshellarg($location.DIRECTORY_SEPARATOR.'manage').' ]';
        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('SSH connection to '.$server.' failed!');
        }
        $output->writeln('<info>Access to '.$server.' granted and working</info>');

        $cmd = '[ ! -d '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user).' ] \\
           && mkdir -p '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/conf.d');

        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('SSH connection to '.$server.' failed!');
        }
        $output->writeln('<info>lduser '.$user.' not implemented yet, working ...</info>');

        $cmd = 'scp '.escapeshellarg(APP_DIR.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'supervisor-user.conf').' '.escapeshellarg(
                $user.'@'.$server.':'.$location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/supervisor.conf');
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config : '.$server.' deploy failed');
        }

        $cmd = 'sed -i "s/__ROOT__/'.str_replace("/","\/",$location).'/g" '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/supervisor.conf');
        $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for  '.$server.' - deploy failed');
        }

        $cmd = 'sed -i "s/__USER__/'.str_replace("/","\/",$user).'/g" '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/supervisor.conf');
        $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for '.$server.' - deploy failed');
        }

        $cmd = '[ ! -d '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/log/supervisor-'.$user).' ] \\
           && mkdir -p '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/log/supervisor-'.$user);

        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }

        $output->writeln('<info>User '.$user.' setup and ready!</info>');

        if(!$skipStart) {
            $cmd = escapeshellarg($location.'/manage').' start-user';
            $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
            exec($cmd,$cmd,$rc);
            if($rc > 0) {
                throw new\Exception('Error starting '.$user.' on '.$server);
            }
            $output->writeln('<info>User '.$user.' started !</info>');

        }

        $minionId = md5($location);

        if(!$skipCron) {
            $cmd = 'crontab -l|grep -q '.escapeshellarg('SALT_USER_'.$minionId);
            $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
            exec($cmd,$cmd,$rc);
            if($rc > 0) {
                $cmd = 'crontab -l | { cat; echo "@reboot '.$location.'/manage start-user # SALT_USER_'.$minionId.'"; } | crontab -';
                $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
                exec($cmd,$cmd,$rc);
                if($rc > 0) {
                    throw new\Exception('Error implementing cron '.$minionId);
                }
            }

        }
    }
}
