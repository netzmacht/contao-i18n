<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Netzmacht\Contao\I18n\NetzmachtContaoI18nBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(NetzmachtContaoI18nBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['18n']),
        ];
    }
}
