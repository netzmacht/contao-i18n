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

use Contao\Database;
use Contao\PageModel;

/**
 * Class AbstractContentSearchableUrlsListener
 */
abstract class AbstractContentSearchableUrlsListener extends AbstractSearchableUrlsListener
{
    /**
     * Legacy contao database connection.
     *
     * @var Database
     */
    private $database;

    /**
     * AbstractContentSearchableUrlsListener constructor.
     *
     * @param Database $database Legacy contao database connection.
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get page child records.
     *
     * @param string|int $pid The parent page id.
     *
     * @return array
     */
    protected function getPageChildRecords($pid): array
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
     *
     * @return bool
     */
    protected function isPagePublished(PageModel $pageModel, int $time): bool
    {
        if (!$pageModel->published) {
            return false;
        }

        if ($pageModel->start != '' && $pageModel->start > $time) {
            return false;
        }

        if ($pageModel->stop != '' && $pageModel->stop <= ($time + 60)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a page should be added to the sitemap. If not being in sitemap mode, always true is returned.
     *
     * @param PageModel $pageModel Page model.
     * @param bool      $isSitemap Sitemap mode.
     *
     * @return bool
     */
    protected function shouldPageBeAddedToSitemap(PageModel $pageModel, bool $isSitemap): bool
    {
        if (!$isSitemap) {
            return true;
        }

        // The target page is protected (see #8416)
        if ($pageModel->protected) {
            return false;
        }

        // The target page is exempt from the sitemap (see #6418)
        if ($pageModel->sitemap == 'map_never') {
            return false;
        }

        return true;
    }
}
