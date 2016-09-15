<?php

namespace Upsalter\Cli;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
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
            ->setDescription('Deploys new chroot package (tar.bz2)')
            ->addArgument('package', InputArgument::REQUIRED, 'Which distribution to build')
            ->addArgument('user', InputArgument::REQUIRED, 'Remote user')
            ->addArgument('server', InputArgument::REQUIRED, 'Server to deploy to')
            ->addArgument('location', InputArgument::REQUIRED, 'Remote directory storing containers')
            ->addArgument('master', InputArgument::REQUIRED, 'Salt master IP/DNS')
            ->addArgument('id', InputArgument::REQUIRED, 'Minion id')
            ->addOption('no-register-key','R',InputOption::VALUE_NONE,'Disable register trough local salt-key')
            ->addOption('skip-start','S',InputOption::VALUE_NONE,'Skip startup of minion (implies -R)')
            ->addOption('skip-cron','C',InputOption::VALUE_NONE,'Skip adding the cron entry for reboot')
            ->addOption('proot-mounts','b',InputOption::VALUE_OPTIONAL + InputOption::VALUE_IS_ARRAY,
                'Additionals proot mounts (eg : /ec or /etc:/etc-host');
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
        $skipCron = $input->getOption('no-register-key');
        $skipStart = $skipRegister ? $skipRegister : $input->getOption('skip-start');
        $prootMounts = $input->getOption('proot-mounts');

        if(!file_exists($input->getArgument('package'))) {
            throw new \Exception('Package not found');
        }
        $output->writeln('<info>Package '.$package.' found !</info>');

        $cmd = '[ -d '.escapeshellarg($location).' ]';
        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('SSH connection to '.$server.' failed!');
        }
        $output->writeln('<info>Access to '.$server.' granted and working</info>');

        $finalLocation = $location.DIRECTORY_SEPARATOR.$minionId;

        $cmd = '[ ! -d '.escapeshellarg($finalLocation).' ] && mkdir '.escapeshellarg($finalLocation);
        exec('ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd),$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Containers '.$minionId.' already deployed, skipping');
        }

        $output->writeln('<info>Container directory '.$finalLocation.' created</info>');

        $output->writeln('<comment>Now deploying container to '.$finalLocation.'</comment>');


        $cmd = 'tar -xvjC '.escapeshellarg($finalLocation);
        $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd).' < '.escapeshellarg($package);
        exec($cmd,$cmdOutput,$rc);
        if($rc > 0) {
            throw new\Exception('Containers '.$minionId.' deploy failed');
        }

        $output->writeln('<info>Container '.$minionId.' deployed</info>');

        //Now deploying scripts :

        $cmd = 'scp '.escapeshellarg(APP_DIR.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'manage.sh').' '.escapeshellarg(
            $user.'@'.$server.':'.$finalLocation.DIRECTORY_SEPARATOR.'manage');
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Manage script '.$minionId.' deploy failed');
        }

        $cmd = 'chmod 755 '.escapeshellarg($finalLocation.DIRECTORY_SEPARATOR.'manage');
        $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Manage chmod failed '.$minionId.' - deploy failed');
        }


        $cmd = 'scp '.escapeshellarg(APP_DIR.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'supervisor-main.conf').' '.escapeshellarg(
                $user.'@'.$server.':'.$finalLocation.DIRECTORY_SEPARATOR.'etc/supervisor/supervisord.conf');
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor main config '.$minionId.' deploy failed');
        }

        $cmd = 'scp '.escapeshellarg(APP_DIR.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'supervisor-minion.conf').' '.escapeshellarg(
                $user.'@'.$server.':'.$finalLocation.DIRECTORY_SEPARATOR.'etc/supervisor/conf.d/salt-minion.conf');
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Supervisor minion config '.$minionId.' deploy failed');
        }

        $tempMinionConfigFile = tempnam(sys_get_temp_dir(),'salt-minion');

        file_put_contents($tempMinionConfigFile,'master: '.$master.PHP_EOL,FILE_APPEND);
        file_put_contents($tempMinionConfigFile,'grains: '.PHP_EOL,FILE_APPEND);
        file_put_contents($tempMinionConfigFile,'  container_path: '.$finalLocation.PHP_EOL,FILE_APPEND);
        file_put_contents($tempMinionConfigFile,'  container_admin_user: '.$user.PHP_EOL,FILE_APPEND);
        file_put_contents($tempMinionConfigFile,'  container_type: upsalter'.PHP_EOL,FILE_APPEND);

        $cmd = 'scp '.escapeshellarg($tempMinionConfigFile).' '.escapeshellarg(
                $user.'@'.$server.':'.$finalLocation.DIRECTORY_SEPARATOR.'etc/salt/minion.d/upsalter.conf');
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Salt config '.$minionId.' deploy failed');
        }
        unlink($tempMinionConfigFile);

        $cmd = 'echo '.escapeshellarg($minionId).' > '.escapeshellarg($finalLocation.'/etc/salt/minion_id');
        $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
        exec($cmd,$cmd,$rc);
        if($rc > 0) {
            throw new\Exception('Minion ID config '.$minionId.' deploy failed');
        }

        $prootArgs = '';

        foreach($prootMounts as $prootMount) {
            $prootArgs .= "-b ".$prootMount.' ';
        }

        if(!empty($prootArgs)) {
            $tempMinionConfigFile = tempnam(sys_get_temp_dir(),'proot-config');

            file_put_contents($tempMinionConfigFile,'export PROOT_ARGS='.escapeshellarg($prootArgs).PHP_EOL,FILE_APPEND);

            $cmd = 'scp '.escapeshellarg($tempMinionConfigFile).' '.escapeshellarg(
                    $user.'@'.$server.':'.$finalLocation.DIRECTORY_SEPARATOR.'proot.cfg');
            exec($cmd,$cmd,$rc);
            if($rc > 0) {
                throw new\Exception('Proot config '.$minionId.' deploy failed');
            }
            unlink($tempMinionConfigFile);
        }

        $output->writeln('<info>Container configuration for '.$minionId.' deployed</info>');



        if(!$skipStart) {
            $cmd = escapeshellarg($finalLocation.'/manage').' start-minion';
            $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
            exec($cmd,$cmd,$rc);
            if($rc > 0) {
                throw new\Exception('Error starting'.$minionId);
            }
            $output->writeln('<info>Minion started, please accept key trough salt-key</info>');
            if(!$skipRegister) {
                //register logic here !
            }
        }

        if(!$skipCron) {
            $cmd = 'crontab -l|grep -q '.escapeshellarg('SALT_'.$minionId);
            $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
            exec($cmd,$cmd,$rc);
            if($rc > 0) {
                $cmd = 'crontab -l | { cat; echo "@reboot '.$finalLocation.'/manage start-minion # SALT_'.$minionId.'"; } | crontab -';
                $cmd = 'ssh -l '.escapeshellarg($user).' '.escapeshellarg($server).' -oBatchMode=yes '.escapeshellarg($cmd);
                exec($cmd,$cmd,$rc);
                if($rc > 0) {
                    throw new\Exception('Error implementing cron '.$minionId);
                }
            }

        }

    }
}
