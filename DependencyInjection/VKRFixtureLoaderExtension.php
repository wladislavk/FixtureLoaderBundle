<?php
namespace VKR\FixtureLoaderBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use VKR\FixtureLoaderBundle\Constants;

class VKRFixtureLoaderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
        $configuration = new Configuration();
        $processedConfiguration = $this->processConfiguration($configuration, $configs);
        $container->setParameter(Constants::FIXTURE_DIRECTORY_PARAM, $processedConfiguration['fixture_directory']);
        $container->setParameter(Constants::FIXTURE_MANAGER_PARAM, $processedConfiguration['fixture_type_manager']);
    }
}
