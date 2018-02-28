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

namespace Netzmacht\Contao\I18n\PageProvider;

use Contao\PageModel;

/**
 * Class PageProvider
 *
 * @package Netzmacht\Contao\I18n\PageProvider
 */
final class PageProvider
{
    /**
     * Current page.
     *
     * @var PageModel|null
     */
    private $page;

    /**
     * Set a page model as current page.
     *
     * @param PageModel $pageModel The page model.
     *
     * @return void
     */
    public function setPage(PageModel $pageModel): void
    {
        $this->page = $pageModel;
    }

    /**
     * Get the current page.
     *
     * @return PageModel|null
     */
    public function getPage(): ?PageModel
    {
        return $this->page;
    }
}
