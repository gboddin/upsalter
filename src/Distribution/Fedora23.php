<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Fedora23 extends Centos7
    {

        public function installSaltMinion()
        {
            $this->prootRun('yum -y install salt-minion');
        }

        public function getRootFsUrl()
        {
            return 'https://github.com/gboddin/linux-rootfs/releases/download/latest/fedora-23.tar.bz2';
        }

        public function getAliases()
        {
            return array(
                'fedora'
            );
        }

        public function getVersions()
        {
            return array(
                23 
            );
        }

    }
}
