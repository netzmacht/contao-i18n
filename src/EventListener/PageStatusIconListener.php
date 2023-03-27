<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

final class PageStatusIconListener
{
    /**
     * Get the page status icon.
     *
     * @param object $pageModel Page model.
     * @param string $icon      The icon.
     */
    public function onGetPageStatusIcon(object $pageModel, string $icon): string
    {
        if ($pageModel->type === 'i18n_regular') {
            return 'bundles/netzmachtcontaoi18n/img/i18n_' . $icon;
        }

        return $icon;
    }
}
