<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\PageModel;
use Netzmacht\Contao\I18n\PageProvider\PageProvider;

final class CurrentPageListener
{
    public function __construct(private PageProvider $pageProvider)
    {
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
