<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos6 extends BaseDistribution {

        const ROOTFS_URL = 'https://github.com/gboddin/linux-rootfs/releases/download/latest/centos-6.7.tar.bz2';

        public function installSaltMinion() {
            $this->enableEpel();
            $this->prootRun('yum -y install salt-minion');
        }

        public function installSupervisor() {

        }

        public function getRootFsUrl() {
            return self::ROOTFS_URL;
        }

        public function getAliases()
        {
            return array(
                'centos'
            );
            // TODO: Implement getAliases() method.
        }

        public function getVersions()
        {
            return array(
              6
            );
            // TODO: Implement getVersion() method.
        }

        public function enableEpel() {
            $this->prootRun('rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm');
        }
    }
}