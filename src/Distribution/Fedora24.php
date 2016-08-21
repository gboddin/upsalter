<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Fedora24 extends Fedora23
    {

        public function getRootFsUrl()
        {
            return 'https://github.com/gboddin/linux-rootfs/releases/download/latest/fedora-24.tar.bz2';
        }

        public function getVersions()
        {
            return array(
                24 
            );
        }

    }
}
