<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos6 extends BaseDistribution
    {

        const ROOTFS_URL = 'https://github.com/gboddin/linux-rootfs/releases/download/latest/centos-6.7.tar.bz2';

        protected $epelEnabled = false;

        public function installSaltMinion()
        {
            $this->enableEpel();
            $this->prootRun('rpm -Uvh https://repo.saltstack.com/yum/redhat/salt-repo-latest-1.el6.noarch.rpm');
            $this->prootRun('yum -y install salt-minion');
        }

        public function installSupervisor()
        {
            $this->enableEpel();
            $this->prootRun('yum install supervisor -y');
            //enabling debian directory structure :
            $this->prootRun('mkdir -p /etc/supervisor/conf.d');
        }

        public function enableEpel()
        {
            if(!$this->epelEnabled)
                $this->prootRun('rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm');
            $this->epelEnabled = true;
        }

        public function init() {

        }

        public function clean() {
            $this->prootRun('yum clean all');
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
        }

        public function getVersions()
        {
            return array(
              6
            );
        }

    }
}
