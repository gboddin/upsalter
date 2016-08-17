<?php
namespace Upsalter\Distribution {

    use Upsalter\BaseDistribution;

    class UbuntuTrusty extends BaseDistribution {

        public function getRootFsUrl() {

        }

        public function installSaltMinion() {

        }

        public function installSupervisor() {

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
              '14.04','trusty'
            );
        }

        public function buildRoot($directory)
        {
            // TODO: Implement buildRoot() method.
        }

    }
}