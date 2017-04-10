<?php
namespace VKR\FixtureLoaderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use VKR\FixtureLoaderBundle\Constants;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('vkr_fixture_loader');
        /** @noinspection PhpUndefinedMethodInspection */
        $rootNode
            ->children()
                ->scalarNode('fixture_directory')
                    ->defaultValue(Constants::DEFAULT_FIXTURE_DIR)
                ->end()
                ->scalarNode('fixture_type_manager')
                    ->defaultValue('vkr_fixture_loader.fixture_type_manager')
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
