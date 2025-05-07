<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Generator;

abstract class ContentSitemapListener
{
    public function __construct(protected readonly ContentUrlGenerator $urlGenerator)
    {
    }

    public function __invoke(SitemapEvent $event): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        foreach ($this->collectPages($event->getRootPageIds()) as $url) {
            $event->addUrlToDefaultUrlSet($url);
        }
    }

    /**
     * Collect all page urls for the given root ids.
     *
     * @param list<int> $rootIds The root page ids
     *
     * @return Generator<string>
     */
    abstract protected function collectPages(array $rootIds): Generator;

    /**
     * Check if a page is published.
     *
     * @param PageModel $pageModel The page model.
     * @param int       $time      The current time.
     */
    protected function isPagePublished(PageModel $pageModel, int $time): bool
    {
        if (! $pageModel->published) {
            return false;
        }

        if ($pageModel->start !== '' && $pageModel->start > $time) {
            return false;
        }

        return $pageModel->stop === '' || $pageModel->stop > $time + 60;
    }

    /**
     * Check if a page should be added to the sitemap. If not being in sitemap mode, always true is returned.
     *
     * @param PageModel $pageModel Page model.
     */
    protected function shouldPageBeAddedToSitemap(PageModel $pageModel): bool
    {
        // The target page is protected (see #8416)
        if ($pageModel->protected) {
            return false;
        }

        // The target page is exempt from the sitemap (see #6418)
        return $pageModel->sitemap !== 'map_never';
    }
}
