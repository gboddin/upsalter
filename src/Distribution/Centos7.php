<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos7 extends BaseDistribution
    {


        public function installSaltMinion()
        {
            $this->enableEpel();
            $this->prootRun('rpm -Uvh https://repo.saltstack.com/yum/redhat/salt-repo-latest-1.el7.noarch.rpm');
            $this->prootRun('yum -y install salt-minion');
        }

        public function installSupervisor()
        {
            $this->prootRun('yum install python-pip -y');
            $this->prootRun('easy_install --upgrade supervisor');
            //enabling debian directory structure :
            $this->prootRun('mkdir -p /etc/supervisor/conf.d');
            $this->prootRun('mkdir -p /var/log/supervisor');
        }

        public function getRootFsUrl()
        {
            return 'https://github.com/gboddin/linux-rootfs/releases/download/20170116/centos-7.tar.bz2';
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
        }
    }
}
