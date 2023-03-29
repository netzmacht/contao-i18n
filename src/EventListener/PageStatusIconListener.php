<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Symfony\Component\Asset\Packages;

final class PageStatusIconListener
{
    public function __construct(private readonly Packages $packages)
    {
    }

    /**
     * Get the page status icon.
     *
     * @param object $pageModel Page model.
     * @param string $icon      The icon.
     */
    public function onGetPageStatusIcon(object $pageModel, string $icon): string
    {
        if ($pageModel->type === 'i18n_regular') {
            return $this->packages->getUrl('img/i18n_' . $icon, 'netzmacht_contao_i18n');
        }

        return $icon;
    }
}
