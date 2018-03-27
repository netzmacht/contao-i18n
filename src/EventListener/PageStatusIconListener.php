<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    contao-18n
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later https://github.com/netzmacht/contao-i18n/blob/master/LICENSE
 * @filesource
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
     * @param string    $icon      The icon.
     *
     * @return string
     */
    public function onGetPageStatusIcon($pageModel, string $icon): string
    {
        if ($pageModel->type === 'i18n_regular') {
            return 'bundles/netzmachtcontaoi18n/img/' . $icon;
        }

        return $icon;
    }
}
