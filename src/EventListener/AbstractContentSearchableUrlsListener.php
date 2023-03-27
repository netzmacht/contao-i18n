<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Database;
use Contao\PageModel;

abstract class AbstractContentSearchableUrlsListener extends AbstractSearchableUrlsListener
{
    /**
     * Legacy contao database connection.
     */
    private Database $database;

    /** @param Database $database Legacy contao database connection. */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get page child records.
     *
     * @param int|string $pid The parent page id.
     *
     * @return array<int|string>
     */
    protected function getPageChildRecords(int|string $pid): array
    {
        if ($pid > 0) {
            return $this->database->getChildRecords($pid, 'tl_page');
        }

        return [];
    }

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
     * @param bool      $isSitemap Sitemap mode.
     */
    protected function shouldPageBeAddedToSitemap(PageModel $pageModel, bool $isSitemap): bool
    {
        if (! $isSitemap) {
            return true;
        }

        // The target page is protected (see #8416)
        if ($pageModel->protected) {
            return false;
        }

        // The target page is exempt from the sitemap (see #6418)
        return $pageModel->sitemap !== 'map_never';
    }
}
