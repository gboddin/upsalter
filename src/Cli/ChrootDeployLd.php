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
            ->addArgument('admin', InputArgument::REQUIRED, 'Remote admin user')
            ->addArgument('user', InputArgument::REQUIRED, 'Remote user')
            ->addArgument('server', InputArgument::REQUIRED, 'Server to deploy to')
            ->addArgument('location', InputArgument::REQUIRED, 'Remote container directory')
            ->addOption('skip-start','S',InputOption::VALUE_NONE,'Skip startup of minion (implies -R)')
            ->addOption('skip-cron','C',InputOption::VALUE_NONE,'Skip adding the cron entry for reboot')
            ->addOption('skip-host-user-sync','U',InputOption::VALUE_NONE,'Skip syncing /etc/passwd and /etc/group from host');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $admin = $input->getArgument('admin');
        $user = $input->getArgument('user');
        $server = $input->getArgument('server');
        $location = $input->getArgument('location');
        $skipStart = $input->getOption('skip-start');
        $skipCron = $input->getOption('skip-cron');
        $cmd = '[ -x '.escapeshellarg($location.DIRECTORY_SEPARATOR.'manage').' ]';
        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('SSH connection to '.$server.' failed!');
        }
        $output->writeln('<info>Access to '.$server.' granted and working</info>');

        $cmd = '[ ! -d '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user).' ] \\
           && mkdir '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user).' \\
           && chown '.$admin.':'.$user.' '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user).' \\
           && chmod 750 '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user).' \\
           && mkdir '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/conf.d');

        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('SSH connection to '.$server.' failed!');
        }
        $output->writeln('<info>lduser '.$user.' not implemented yet, working ...</info>');

        $cmd = 'scp '.escapeshellarg(APP_DIR.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'supervisor-user.conf').' '.escapeshellarg(
                $admin.'@'.$server.':'.$location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/supervisor.conf');
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config : '.$server.' deploy failed');
        }

        $cmd = 'sed -i "s/__ROOT__/'.str_replace("/","\/",$location).'/g" '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/supervisor.conf');
        $cmd = 'ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for  '.$server.' - deploy failed');
        }

        $cmd = 'sed -i "s/__USER__/'.str_replace("/","\/",$user).'/g" '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/supervisor-'.$user.'/supervisor.conf');
        $cmd = 'ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for '.$server.' - deploy failed');
        }

        $cmd = '[ ! -d '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/log/supervisor-'.$user).' ] \\
           && mkdir -p '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/log/supervisor-'.$user).' \\
           && chown '.$admin.':'.$user.' '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/log/supervisor-'.$user).' \\
           && chmod 770 '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/log/supervisor-'.$user);

        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }

        $cmd = '[ ! -d '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/run/supervisor-'.$user).' ] \\
           && mkdir -p '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/run/supervisor-'.$user).' \\
           && chown '.$admin.':'.$user.' '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/run/supervisor-'.$user).' \\
           && chmod 2770 '.escapeshellarg($location.DIRECTORY_SEPARATOR.'var/run/supervisor-'.$user);

        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }

        $output->writeln('<info>lduser '.$user.' syncing passwd files ...</info>');

        // Syncronize passwd and group file from container :
        $cmd = 'cat '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/passwd').' \\
                | grep -q "^'.$user.'\:" || \\
                  (cat /etc/passwd |grep "^'.$user.'\:"  \\
                  >> '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/passwd').')';
        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }

        $cmd = 'cat '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/group').' \\
                | grep -q "^'.$user.'\:" || \\
                  (cat /etc/group |grep "^'.$user.'\:"  \\
                  >> '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/group').')';
        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }

        $cmd = 'cat '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/passwd').' \\
                | grep -q "^'.$admin.'\:" || \\
                  (cat /etc/passwd |grep "^'.$admin.'\:"  \\
                  >> '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/passwd').')';
        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }

        $cmd = 'cat '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/group').' \\
                | grep -q "^'.$admin.'\:" || \\
                  (cat /etc/group |grep "^'.$admin.'\:"  \\
                  >> '.escapeshellarg($location.DIRECTORY_SEPARATOR.'etc/group').')';
        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }
        //End syncronize ser


        $output->writeln('<info>User '.$user.' setup and ready!</info>');

        // make sure /tmp is writable
        $cmd = 'chmod 777 '.escapeshellarg($location.DIRECTORY_SEPARATOR.'tmp');
        exec('ssh -l '.escapeshellarg($admin).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor user config for'.$server.' failed!');
        }


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
