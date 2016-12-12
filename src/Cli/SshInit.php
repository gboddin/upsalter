<?php

namespace Upsalter\Cli;

use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use \Symfony\Component\Console\Output\OutputInterface;

class SshInit extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('ssh:init')
            // the short description shown while running "php bin/console list"
            ->setDescription('Resets a bunch for ssh hosts passwords from csv')
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file to use')
            ->addOption('authorize','a',InputOption::VALUE_REQUIRED,'Deploys a file as .ssh/authorized_keys')
            ->addOption('randomize','r',InputOption::VALUE_NONE,'Randomize the password selection and display it');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $logger = new ConsoleLogger($output);
        $csvFile = $input->getArgument('file');
        $authFile = $input->getOption('authorize');
        $randomPasswd = $input->getOption('randomize');

        if(!file_exists($csvFile))
            throw new \Exception('File '.$csvFile.' not found',404);

        if(!empty($authFile) && !file_exists($authFile))
            throw new \Exception('Authfile '.$authFile.' not found!',404);

        $serversToReset = array();
        $row = 1;
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
                if($num < 3)
                    throw new \Exception('Line needs at least 3 fields : hostname,user,password,(newpassword)');
                if($num > 3 ) {
                    $newPassword = $data[3];
                }
                if($randomPasswd||$num < 4) {
                    $generator = new ComputerPasswordGenerator();

                    $generator
                        ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
                        ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
                        ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
                        ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, false)
                        ->setLength(16);

                    $newPassword = $generator->generatePassword();
                }
                if(empty($newPassword) || strlen($newPassword) < 12)
                    throw new \Exception('New password validation error : '.$newPassword);
                $serversToReset[] = array(
                  $data[0],$data[1],$data[2],$newPassword
                );
            }
            fclose($handle);

            $logger->info(count($serversToReset).' servers loaded in memory, processing ...');

            //we're ready to iterate :

            foreach($serversToReset as $serverToReset) {
                $logger->info('Resetting password for '.$serverToReset[0]);
                $resetScript = APP_DIR.'/bin/ssh-reset-wrapper.sh';
                passthru($resetScript.' '.
                    escapeshellarg($serverToReset[0]).' '.
                    escapeshellarg($serverToReset[1]).' '.
                    escapeshellarg($serverToReset[2]).' '.
                    escapeshellarg($serverToReset[3])
                );
                if($authFile) {
                    $logger->info('Sending public key file to '.$serverToReset[0]);
                    $sshConnection = ssh2_connect($serverToReset[0], 22);
                    if(!ssh2_auth_password($sshConnection, $serverToReset[1],$serverToReset[3])) {
                        throw new \Exception('Authentication to '.$serverToReset[0].' failed');
                    }
                    $sftpService = ssh2_sftp($sshConnection);
                    ssh2_sftp_mkdir($sftpService, '.ssh',0700);
                    if(!ssh2_scp_send($sshConnection, $authFile, '.ssh/authorized_keys', 0600)) {
                        throw new \Exception('SSH key drop to '.$serverToReset[0].' failed');
                    }
                }
            }
            $logger->info('Done');

        }

    }
}
