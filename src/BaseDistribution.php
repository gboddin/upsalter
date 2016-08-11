<?php
namespace Upsalter {
    abstract class BaseDistribution
    {
        /**
         * @return array
         */
        abstract public function getAliases();

        /**
         * @return array
         */
        abstract public function getVersions();

        /**
         * @param $directory
         * @return bool
         */
        abstract public function buildRoot($directory);

        /**
         * @param null $dir
         * @param null $prefix
         * @return string
         */
        public function getTempDir($dir=NULL,$prefix=NULL) {
                $template = "{$prefix}XXXXXX";
                if (($dir) && (is_dir($dir))) { $tmpdir = "--tmpdir=$dir"; }
                else { $tmpdir = '--tmpdir=' . sys_get_temp_dir(); }
                return exec("mktemp -d $tmpdir $template");
        }

    }
}