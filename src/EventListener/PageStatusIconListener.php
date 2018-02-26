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

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\PageModel;

/**
 * Class PageStatusIconListener
 */
class PageStatusIconListener
{
    /**
     * Get the page status icon.
     *
     * @param PageModel $pageModel Page model.
     * @param string    $icon      The icon
     *
     * @return string
     */
    public function onGetPageStatusIcon($pageModel, string $icon): string
    {
        if ($pageModel->type === 'i18n_regular') {
            return 'web/bundles/netzmachtcontaoi18n/img/' . substr($icon, 0, -3) . 'gif';
        }

        return $icon;
    }
}
