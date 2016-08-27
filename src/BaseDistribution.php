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

        private $saltPlugins;

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
         * @return mixed
         */
        abstract public function init();

        /**
         * @return mixed
         */
        abstract public function clean();

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

                $this->init();

                $this->installSaltMinion();
                $this->installSupervisor();
                $this->installSaltPlugins();

                $this->clean();
                $this->package($this->rootDir, $target);

            } catch (\Exception $e) {
                var_dump($this);
                throw $e;
            }
        }

        public function addSaltPlugins($plugins) {
            $this->saltPlugins = $plugins;
        }

        private function installSaltPlugins() {
            $this->prootRun('mkdir -p /srv/salt');
            copy(APP_DIR.DIRECTORY_SEPARATOR.'resources/salt-top.sls',$this->rootDir.'/srv/salt/top.sls');

            foreach($this->saltPlugins as $plugin) {
                copy(APP_DIR.DIRECTORY_SEPARATOR.'resources/salt-plugins/'.$plugin.'.sls',
                    $this->rootDir.'/srv/salt/'.$plugin.'.sls');
                file_put_contents($this->rootDir.'/srv/salt/top.sls','    - '.$plugin.PHP_EOL,FILE_APPEND);
            }


            if(count($this->saltPlugins) > 0)
                $this->prootRun('salt-call --local state.apply');



        }

        public function prootRun($cmd)
        {
            return passthru('PATH=/bin:/sbin:/usr/bin:/usr/sbin '.
                $this->rootDir.DIRECTORY_SEPARATOR.'proot -S '.escapeshellarg($this->rootDir).' sh -axc '.escapeshellarg($cmd), $rc);
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
            exec('tar --exclude="dev/*" -xjvC '.escapeshellarg($target).' -f '.escapeshellarg($package), $output, $rc);
            if ($rc > 0) {
                throw new \Exception('Package failed : '.PHP_EOL.implode(PHP_EOL, $output));
            }
            // Fix bad permissions :
            exec('find '.escapeshellarg($target).' -perm 000 -exec chmod 400 {} \;');
        }

        public function package($dir, $target)
        {
            exec('tar -cjC '.
                escapeshellarg($dir).' . > '.escapeshellarg($target), $output, $rc);
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
