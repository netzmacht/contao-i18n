<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageModel;
use Netzmacht\Contao\I18n\PageProvider\PageProvider;

final class CurrentPageListener
{
    public function __construct(private readonly PageProvider $pageProvider)
    {
    }

    /**
     * Set the page layout.
     *
     * @param PageModel $pageModel The page layout.
     */
    #[AsHook('getPageLayout')]
    public function onGetPageLayout(PageModel $pageModel): void
    {
        $this->pageProvider->setPage($pageModel);
    }
}
