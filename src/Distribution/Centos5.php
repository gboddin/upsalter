<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos5 extends BaseDistribution
    {

        protected $epelEnabled = false;

        public function installSaltMinion()
        {
            $this->enableEpel();
            $this->prootRun('rpm -Uvh http://repo.saltstack.com/yum/redhat/salt-repo-latest-1.el5.noarch.rpm');
            $this->prootRun('yum update -y');
            $this->prootRun('yum -y install salt-minion');
        }

        public function installSupervisor()
        {
            $this->enableEpel();
            $this->prootRun('yum install python26-pip -y');
            $this->prootRun('easy_install-2.6 --upgrade supervisor');
            //enabling debian directory structure :
            $this->prootRun('mkdir -p /etc/supervisor/conf.d');
            $this->prootRun('mkdir -p /var/log/supervisor');
        }

        public function enableEpel()
        {
            if(!$this->epelEnabled)
                $this->prootRun('rpm -Uvh http://dl.fedoraproject.org/pub/epel/epel-release-latest-5.noarch.rpm');
                $this->epelEnabled = true;
        }

        public function init() {
          $this->prootRun('yum update -y');
        }

        public function clean() {
            $this->prootRun('yum clean all');
        }

        public function getRootFsUrl()
        {
            return 'https://github.com/gboddin/linux-rootfs/releases/download/20170116/centos-5.11.tar.bz2';
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
              5
            );
        }

    }
}
