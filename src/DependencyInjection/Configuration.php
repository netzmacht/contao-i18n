<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root        = $treeBuilder->root('netzmacht_contao_i18n');

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
