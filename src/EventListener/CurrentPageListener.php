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
use Netzmacht\Contao\I18n\PageProvider\PageProvider;

/**
 * Class CurrentPageListener
 */
class CurrentPageListener
{
    /**
     * Page provider.
     *
     * @var PageProvider
     */
    private $pageProvider;

    /**
     * CurrentPageListener constructor.
     *
     * @param PageProvider $pageProvider The page provider.
     */
    public function __construct(PageProvider $pageProvider)
    {
        $this->pageProvider = $pageProvider;
    }

    /**
     * Set the page layout.
     *
     * @param PageModel $pageModel The page layout.
     *
     * @return void
     */
    public function onGetPageLayout(PageModel $pageModel): void
    {
        $this->pageProvider->setPage($pageModel);
    }
}
