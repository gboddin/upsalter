<?php
namespace Upsalter {

    abstract class BaseDistribution
    {

        /**
         * @var $tempDir
         */
        private $tempDir;
        /**
         * @var $rootFsPackage
         */
        private $rootFsPackage;

        private $rootDir;

        /**
         * @return string Rootfs url
         */
        abstract public function getRootFsUrl();

        /**
         * @return array
         */
        abstract public function getAliases();

        /**
         * @return array
         */
        abstract public function getVersions();

        /**
         * @param $root
         * @return boolean
         */
        abstract public function installSupervisor();

        /**
         * @param $root
         * @return boolean
         */
        abstract public function installSaltMinion();


        /**
         * @param $target
         */
        public function build($target)
        {
            /**
             * Prepare root build dir and configure build :
             */
            $this->tempDir = $this->getTempDir();

            try {
                $this->rootDir = $this->tempDir.DIRECTORY_SEPARATOR.'root';

                $this->rootFsPackage = $this->downloadRootFs();

                $this->extract($this->rootFsPackage, $this->rootDir);
                /**
                 * Install proot :
                 */
                $this->downloadProot();

                $this->installSaltMinion();
                $this->installSupervisor();

                $this->package($this->rootDir, $target);
            } catch (\Exception $e) {
                var_dump($this);
                throw $e;
            }
        }

        public function prootRun($cmd)
        {
            return passthru($this->rootDir.DIRECTORY_SEPARATOR.'proot -S '.escapeshellarg($this->rootDir).' '.$cmd, $rc);
        }

        /**
         * @param $package
         * @param $target
         */
        public function extract($package, $target)
        {
            if (!is_dir(dirname($target))) {
                throw new \Exception('Extrac failed : '.dirname($target).'doesn\'t exists');
            }

            /** @noinspection MkdirRaceConditionInspection */
            mkdir($target);
            exec('tar -xjvC '.escapeshellarg($target).' -f '.escapeshellarg($package), $output, $rc);
            if ($rc > 0) {
                throw new \Exception('Package failed : '.PHP_EOL.implode(PHP_EOL, $output));
            }
        }

        public function package($dir, $target)
        {
            exec('tar cjC '.escapeshellarg($dir).' . > '.escapeshellarg($target), $output, $rc);
            if ($rc > 0) {
                throw new \Exception('Package failed : '.PHP_EOL.implode(PHP_EOL, $output));
            }
        }

        /**
         * @param null $dir
         * @param null $prefix
         * @return string
         */
        public function getTempDir($dir = null, $prefix = null)
        {
                $template = "{$prefix}XXXXXX";
            if (($dir) && (is_dir($dir))) {
                $tmpdir = "--tmpdir=$dir";
            } else {
                $tmpdir = '--tmpdir=' . sys_get_temp_dir();
            }
                return exec("mktemp -d $tmpdir $template");
        }

        /**
         * @param $target
         * @throws \Exception
         */
        public function downloadRootFs()
        {
            $cache_key = md5($this->getRootFsUrl());
            $cache_file = getenv('HOME').DIRECTORY_SEPARATOR.'.upsalter/cache-distro-'.$cache_key.'.tar.bz2';
            if (!is_dir(dirname($cache_file))) {
                /** @noinspection MkdirRaceConditionInspection */
                mkdir(dirname($cache_file));
            }
            exec('wget -c '.escapeshellarg($this->getRootFsUrl()).' -qO '.escapeshellarg($cache_file), $output, $rc);
            if ($rc > 0) {
                throw new \Exception('Couldn\'t download '.$this->getRootFsUrl());
            }
            return $cache_file;
        }

        /**
         * @param $destination
         * @throws \Exception
         */
        public function downloadProot()
        {
            exec('wget '.escapeshellarg('https://raw.githubusercontent.com/proot-me/proot-static-build/master/static/proot-x86_64').' -qO '.
                escapeshellarg($this->rootDir.DIRECTORY_SEPARATOR.'proot'), $output, $rc);
            if ($rc > 0) {
                throw new \Exception('Couldn\'t download proot : '.PHP_EOL.implode(PHP_EOL, $output));
            }
            chmod($this->rootDir.DIRECTORY_SEPARATOR.'proot', 0755);
        }
    }
}
