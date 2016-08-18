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
            ->setDescription('Deploys new chroot package (tar.bz2)')
            ->addArgument('package', InputArgument::REQUIRED, 'Which distribution to build')
            ->addArgument('user', InputArgument::REQUIRED, 'Remote user')
            ->addArgument('server', InputArgument::REQUIRED, 'Server to deploy to')
            ->addArgument('location', InputArgument::REQUIRED, 'Remote directory storing containers')
            ->addArgument('master', InputArgument::REQUIRED, 'Salt master IP/DNS')
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

    }
}
