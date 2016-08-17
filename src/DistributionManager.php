<?php
namespace Upsalter {

    class DistributionManager
    {
        private $distros = array();

        public function __construct()
        {
            $distros = glob(__DIR__.DIRECTORY_SEPARATOR.'Distribution'.DIRECTORY_SEPARATOR.'*.php');
            foreach ($distros as $distro) {
                $distro = str_replace(__DIR__, '', $distro);
                $distro = str_replace('.php', '', $distro);
                $distro = str_replace('/', '\\', $distro);
                $distro = '\\Upsalter'.$distro;
                $this->distros[] = new $distro;
            }
        }

        /**
         * @return BaseDistribution[]
         */
        public function getDistributions()
        {
            return $this->distros;
        }

        /**
         * @param $distributionName
         * @param $distributionVersion
         * @return BaseDistribution
         * @throws \Exception
         */
        public function getDistro($distributionName, $distributionVersion)
        {
            foreach ($this->getDistributions() as $distribution) {
                foreach ($distribution->getAliases() as $distributionAlias) {
                    if ($distributionAlias == strtolower($distributionName)) {
                        foreach ($distribution->getVersions() as $distributionVersionAlias) {
                            if (strtolower($distributionVersion) == $distributionVersionAlias) {
                                return $distribution;
                            }
                        }
                    }
                }
            }
            throw new \Exception('Distrubtion '.$distributionName.' '.$distributionVersion.' not found !');
        }
    }
}
