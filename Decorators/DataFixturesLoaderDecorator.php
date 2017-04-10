<?php
namespace VKR\FixtureLoaderBundle\Decorators;

use Doctrine\Common\DataFixtures\Loader;

class DataFixturesLoaderDecorator
{
    public function getNewLoader()
    {
        return new Loader();
    }
}
