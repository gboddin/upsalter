<?php
namespace Upsalter {

    use Psr\Log\LoggerInterface;

    abstract class BaseDistribution
    {

        /**
         * @var $tempDir string Location of the temporary directory
         */
        private $tempDir;

        /**
         * @var $rootFsPackage string Location of the downloaded rootfs package
         */
        private $rootFsPackage;

        /**
         * @var $rootDir string Location of the current build
         */
        private $rootDir;

        /**
         * @var $saltPlugins array List of saltstack recipe to process
         */
        private $saltPlugins;

        /**
         * @var $logger PSR-2 complient logger
         */
        private $logger;

        /**
         * @return string Rootfs url
         */
        abstract public function getRootFsUrl();

        /**
         * @return array List of distribution aliases
         */
        abstract public function getAliases();

        /**
         * @return array List of distribution versions aliases
         */
        abstract public function getVersions();

        /**
         * Install supervisor daemon
         *
         * @throws \Exception
         *
         */
        abstract public function installSupervisor();

        /**
         * Install saltstack repository and minion package
         *
         * @throws \Exception
         */
        abstract public function installSaltMinion();

        /**
         * Run tasks to perform before building
         *
         * @throws \Exception
         */
        abstract public function init();

        /**
         * Clear the built environment from remaining packages
         *
         * @throws \Exception
         */
        abstract public function clean();

        /**
         * BaseDistribution constructor.
         * @param $logger LoggerInterface
         */
        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
        }

        /**
         * @param $target
         * @throws \Exception
         */
        public function build($target)
        {
            $this->logger->notice('Started building salted container');

            /**
             * Prepare root build dir and configure build :
             */
            $this->tempDir = $this->getTempDir();

            try {
                $this->rootDir = $this->tempDir.DIRECTORY_SEPARATOR.'root';

                $this->logger->info('Downloading '.$this->getRootFsUrl());
                $this->rootFsPackage = $this->downloadRootFs();

                $this->logger->info('Extracting '.$this->rootFsPackage);
                $this->extract($this->rootFsPackage, $this->rootDir);

                /**
                 * Install proot :
                 */
                $this->downloadProot();

                $this->logger->debug('Calling init() hook');
                $this->init();

                $this->logger->debug('Calling installSaltMinion() hook');
                $this->installSaltMinion();
                $this->logger->debug('Calling installSupervisor() hook');
                $this->installSupervisor();
                $this->logger->debug('Calling installSaltPlugins() hook');
                $this->installSaltPlugins();

                $this->logger->debug('Calling clean() hook');
                $this->clean();

                $this->logger->info('Packaging result in '.$target);
                $this->package($this->rootDir, $target);

            } catch (\Exception $e) {
                $this->logger->critical('Build failed !');
                var_dump($this);
                throw $e;
            }
        }

        /**
         * Set the saltstack plugins to process
         *
         * @param $plugins array Array of plugins
         */
        public function addSaltPlugins($plugins) {
            $this->saltPlugins = $plugins;
        }

        /**
         * Install the plugins in the salttree and call state.highstate
         *
         * @throws \Exception
         */
        private function installSaltPlugins() {
            //If there's no plugin, continue
            if(count($this->saltPlugins) < 1) {
                $this->logger->debug('No Saltstack plugins to install');
                return true;
            }

            // Create state tree
            $this->prootRun('mkdir -p /srv/salt');

            //Prepare top.sls
            copy(APP_DIR.DIRECTORY_SEPARATOR.'resources/salt-top.sls',$this->rootDir.'/srv/salt/top.sls');

            //Register recipes
            foreach($this->saltPlugins as $plugin) {
                $this->logger->info('Preparing Saltack plugin '.$plugin);
                copy(APP_DIR.DIRECTORY_SEPARATOR.'resources/salt-plugins/'.$plugin.'.sls',
                    $this->rootDir.'/srv/salt/'.$plugin.'.sls');
                file_put_contents($this->rootDir.'/srv/salt/top.sls','    - '.$plugin.PHP_EOL,FILE_APPEND);
            }

            $this->logger->info('Running Saltstack state.highstate');
            // Call state.highstate
            return $this->prootRun('salt-call --local state.highstate');
        }

        /**
         * Run a command inside the build container with proot
         *
         * @param $cmd
         * @return integer Return code
         *
         */
        public function prootRun($cmd)
        {
            $this->logger->debug('prootRun : '.$cmd);
            passthru('PATH=/bin:/sbin:/usr/bin:/usr/sbin '.
                $this->rootDir.DIRECTORY_SEPARATOR.'proot -S '.escapeshellarg($this->rootDir).' sh -axc '.escapeshellarg($cmd), $rc);
            if($rc > 0) {
                throw new \Exception('Proot run failed !');
            }
            return $rc;
        }

        /**
         * @param $package
         * @param $target
         */
        public function extract($package, $target)
        {
            $this->logger->debug('Extracting '.$package.' in '.$target);
            if (!is_dir(dirname($target))) {
                throw new \Exception('Extract failed : '.dirname($target).'doesn\'t exists');
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
            // Fix bad permissions :
            exec('find '.escapeshellarg($dir).' -exec chmod u+r {} \; 2> /dev/null');
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
            $this->logger->info('Downloading and installing proot');
            exec('wget '.escapeshellarg('https://raw.githubusercontent.com/proot-me/proot-static-build/master/static/proot-x86_64').' -qO '.
                escapeshellarg($this->rootDir.DIRECTORY_SEPARATOR.'proot'), $output, $rc);
            if ($rc > 0) {
                throw new \Exception('Couldn\'t download proot : '.PHP_EOL.implode(PHP_EOL, $output));
            }
            chmod($this->rootDir.DIRECTORY_SEPARATOR.'proot', 0755);
        }
    }
}
