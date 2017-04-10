<?php
namespace VKR\FixtureLoaderBundle\Decorators;

class FilesystemDecorator
{
    /**
     * @param string $dirname
     * @return bool
     */
    public function isDir($dirname)
    {
        return is_dir($dirname);
    }
}
