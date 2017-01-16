<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class UbuntuXenial extends BaseDistribution
    {
        const ROOTFS_URL = 'https://github.com/gboddin/linux-rootfs/releases/download/20170116/ubuntu-xenial.tar.bz2';

        public function getRootFsUrl()
        {
            return self::ROOTFS_URL;
        }

        public function installSaltMinion()
        {
            $this->enableSaltRepo();
            $this->prootRun('apt-get install salt-minion -y');
        }

        public function installSupervisor()
        {
            $this->prootRun('apt-get install supervisor -y');
        }

        public function init(){
            $this->prootRun('apt-get update');
        }

        public function clean(){
            $this->prootRun('apt-get clean');
        }

        public function getAliases()
        {
            return array(
                'ubuntu'
            );
        }

        public function getVersions()
        {
            return array(
              '16.04','xenial'
            );
        }

        public function enableSaltRepo() {
            $this->prootRun('apt-get install wget -y');
            $this->prootRun('wget -O - https://repo.saltstack.com/apt/ubuntu/16.04/amd64/latest/SALTSTACK-GPG-KEY.pub |  apt-key add -');
            $this->prootRun('echo deb http://repo.saltstack.com/apt/ubuntu/16.04/amd64/latest xenial main >> /etc/apt/sources.list.d/saltstack.list');
            $this->prootRUn('apt-get update');
        }
    }
}
