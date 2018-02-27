<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Class NetzmachtContaoI18nExtension
 */
final class NetzmachtContaoI18nExtension extends Extension
{
    /**
     * Map of the bundle and the corresponding searchable pages listener.
     *
     * @var array
     */
    private $searchablePagesListenersMap = [
        'ContaoCalendarBundle' => 'netzmacht.contao_i18n.listeners.searchable_events',
        'ContaoFaqBundle'      => 'netzmacht.contao_i18n.listeners.searchable_faqs',
        'ContaoNewsBundle'     => 'netzmacht.contao_i18n.listeners.searchable_news',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(dirname(__DIR__) . '/Resources/config')
        );

        $loader->load('services.xml');

        $this->configureSearchablePagesListeners($container);
    }

    /**
     * Disable searchable pages listeners if the bundle is not available in the installation.
     *
     * @param ContainerBuilder $container The container builder.
     *
     * @return void
     */
    private function configureSearchablePagesListeners(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        foreach ($this->searchablePagesListenersMap as $bundleName => $serviceId) {
            if (!isset($bundles[$bundleName])) {
                $container->removeDefinition($serviceId);
            }
        }
    }
}
