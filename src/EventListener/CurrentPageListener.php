<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\PageModel;
use Netzmacht\Contao\I18n\PageProvider\PageProvider;

class CurrentPageListener
{
    private PageProvider $pageProvider;

    public function __construct(PageProvider $pageProvider)
    {
        $this->pageProvider = $pageProvider;
    }

    /**
     * Set the page layout.
     *
     * @param PageModel $pageModel The page layout.
     */
    public function onGetPageLayout(PageModel $pageModel): void
    {
        $this->pageProvider->setPage($pageModel);
    }
}
