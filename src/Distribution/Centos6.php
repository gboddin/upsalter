<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class Centos6 extends BaseDistribution {

        const ROOTFS_URL = 'https://github.com/gboddin/linux-rootfs/releases/download/20160811/centos-6.7.tar.bz2';

        public function getAliases()
        {
            return array(
                'centos'
            );
            // TODO: Implement getAliases() method.
        }

        public function buildRoot($directory)
        {
            shell_exec('wget '.escapeshellarg(self::ROOTFS_URL).' -O -|tar -xjvC '.escapeshellarg($directory));
        }

        public function getVersions()
        {
            return array(
              6
            );
            // TODO: Implement getVersion() method.
        }
    }
}