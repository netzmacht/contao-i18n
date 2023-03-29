<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('netzmacht_contao_i18n');
        $root        = $treeBuilder->getRootNode();

        $root
            ->children()
                ->booleanNode('article_cleanup')
                    ->info('If enabled unused articles in the i18n regular page are deleted.')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
