<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Netzmacht\Contao\I18n\EventListener\I18nEventSitemapListener;
use Netzmacht\Contao\I18n\EventListener\I18nFaqSitemapListener;
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
     * Map of the bundle and the corresponding optional services.
     *
     * @var array<string,list<string>>
     */
    private array $optionalServices = [
        'ContaoCalendarBundle' => [I18nEventSitemapListener::class, I18nCalendarEventsResolver::class],
        'ContaoFaqBundle'      => [I18nFaqSitemapListener::class, I18nFaqResolver::class],
        'ContaoNewsBundle'     => [I18nNewsResolver::class, I18nEventSitemapListener::class],
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

        $this->removeUnusedServices($container);

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('netzmacht.contao_i18n.article_cleanup', $config['article_cleanup']);
    }

    /**
     * Remove unused services if the corresponding contao bundle is not there
     *
     * @param ContainerBuilder $container The container builder.
     */
    private function removeUnusedServices(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        assert(is_array($bundles));

        foreach ($this->optionalServices as $bundleName => $listeners) {
            if (isset($bundles[$bundleName])) {
                continue;
            }

            foreach ($listeners as $serviceId) {
                $container->removeDefinition($serviceId);
            }
        }
    }
}
