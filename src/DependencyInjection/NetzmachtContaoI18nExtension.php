<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Netzmacht\Contao\I18n\Routing\Content\I18nCalendarEventsResolver;
use Netzmacht\Contao\I18n\Routing\Content\I18nFaqResolver;
use Netzmacht\Contao\I18n\Routing\Content\I18nNewsResolver;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use function assert;
use function is_array;

/** @SuppressWarnings(PHPMD.LongVariable) */
final class NetzmachtContaoI18nExtension extends Extension
{
    /**
     * Map of the bundle and the corresponding searchable pages listener.
     *
     * @var array<string,string>
     */
    private array $searchablePagesListeners = [
        'ContaoCalendarBundle' => 'netzmacht.contao_i18n.listeners.searchable_events',
        'ContaoFaqBundle'      => 'netzmacht.contao_i18n.listeners.searchable_faqs',
        'ContaoNewsBundle'     => 'netzmacht.contao_i18n.listeners.searchable_news',
    ];

    /**
     * Map of the bundle and the corresponding url resolver.
     *
     * @var array<string,string>
     */
    private array $urlResolvers = [
        'ContaoCalendarBundle' => I18nCalendarEventsResolver::class,
        'ContaoFaqBundle'      => I18nFaqResolver::class,
        'ContaoNewsBundle'     => I18nNewsResolver::class,
    ];

    /** {@inheritDoc} */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $loader->load('services.xml');

        $this->configureSearchablePagesListeners($container);

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('netzmacht.contao_i18n.article_cleanup', $config['article_cleanup']);
    }

    /**
     * Disable searchable pages listeners if the bundle is not available in the installation.
     *
     * @param ContainerBuilder $container The container builder.
     */
    private function configureSearchablePagesListeners(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        assert(is_array($bundles));

        foreach ($this->searchablePagesListeners as $bundleName => $serviceId) {
            if (isset($bundles[$bundleName])) {
                continue;
            }

            $container->removeDefinition($serviceId);
        }

        foreach ($this->urlResolvers as $bundleName => $serviceId) {
            if (isset($bundles[$bundleName])) {
                continue;
            }

            $container->removeDefinition($serviceId);
        }
    }
}
