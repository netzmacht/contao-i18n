<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\PageProvider;

use Contao\PageModel;

final class PageProvider
{
    /**
     * Current page.
     */
    private PageModel|null $page = null;

    /**
     * Set a page model as current page.
     *
     * @param PageModel $pageModel The page model.
     */
    public function setPage(PageModel $pageModel): void
    {
        $this->page = $pageModel;
    }

    /**
     * Get the current page.
     */
    public function getPage(): PageModel|null
    {
        return $this->page;
    }
}
