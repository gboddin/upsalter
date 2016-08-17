<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos7 extends BaseDistribution
    {

        const ROOTFS_URL = 'https://github.com/gboddin/linux-rootfs/releases/download/latest/centos-7.tar.bz2';

        public function installSaltMinion()
        {
            $this->enableEpel();
            $this->prootRun('yum -y install salt-minion');
        }

        public function installSupervisor()
        {
        }

        public function getRootFsUrl()
        {
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
                7
            );
        }

        public function init() {

        }

        public function clean() {
            $this->prootRun('yum clean all');
        }

        public function enableEpel()
        {
            $this->prootRun('rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm');
            $this->prootRun('rpm -Uvh https://repo.saltstack.com/yum/redhat/salt-repo-latest-1.el7.noarch.rpm');
        }
    }
}
