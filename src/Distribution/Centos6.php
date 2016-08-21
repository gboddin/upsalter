<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos6 extends BaseDistribution
    {

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
            $this->prootRun('yum install python-pip -y');
            $this->prootRun('easy_install --upgrade supervisor');
            //enabling debian directory structure :
            $this->prootRun('mkdir -p /etc/supervisor/conf.d');
            $this->prootRun('mkdir -p /var/log/supervisor');
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
            return 'https://github.com/gboddin/linux-rootfs/releases/download/latest/centos-6.8.tar.bz2';
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
