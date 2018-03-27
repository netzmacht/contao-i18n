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

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Date;
use Contao\Model\Registry;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\I18n\Model\Article\PageArticlesWithTeasersQuery;
use Netzmacht\Contao\I18n\Model\Page\PublishedI18nRegularPagesQuery;

/**
 * SitemapBuilder adds i18n_regular pages to the sitemap.
 *
 * @package Netzmacht\Contao\I18n\SearchIndex
 */
class SearchableI18nRegularPageUrlsListener extends AbstractSearchableUrlsListener
{
    /**
     * Database connection.
     *
     * @var \Database
     */
    private $connection;

    /**
     * Model registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Contao config adapter.
     *
     * @var Config|Adapter
     */
    private $config;

    /**
     * Construct.
     *
     * @param Connection     $connection Database connection.
     * @param Registry       $registry   Model registry.
     * @param Config|Adapter $config     Contao config adapter.
     */
    public function __construct(Connection $connection, Registry $registry, $config)
    {
        $this->connection = $connection;
        $this->registry   = $registry;
        $this->config     = $config;
    }

    /**
     * Get all searchable i18n pages and return them as array.
     *
     * Stolen from Backend::findSearchablePages
     *
     * @param int    $pid       Parent id.
     * @param string $domain    Domain name.
     * @param bool   $isSitemap Fetch for the sitemap Sitemap.
     *
     * @see    https://github.com/contao/core/blob/master/system/modules/core/classes/Backend.php
     * @return array
     */
    protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array
    {
        $time      = Date::floorToMinute();
        $query     = new PublishedI18nRegularPagesQuery($this->connection);
        $statement = $query->execute($time, $pid);

        // Get published pages
        $pages = array();

        // Recursively walk through all subpages
        while ($result = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $page = $this->createModel($result);

            if ($this->shouldPageBeAdded($page, $isSitemap, $time)) {
                $pages[] = $page->getAbsoluteUrl();

                // Get articles with teaser
                $query    = new PageArticlesWithTeasersQuery($this->connection);
                $articles = $query->execute((int) $result['id'], $time);

                while ($article = $articles->fetch(\PDO::FETCH_OBJ)) {
                    // Do not show pages without a translation. They are ignored.
                    if ($article->languageMain > 0) {
                        continue;
                    }

                    $pages[] = sprintf(
                        $page->getAbsoluteUrl('/articles/%s'),
                        (($article->alias != '' && !$this->config->get('disableAlias'))
                            ? $article->alias
                            : $article->id
                        )
                    );
                }
            }

            // Get subpages
            if ($this->shouldBeIndexed($page)
                && ($subPages = $this->collectPages($page->id, $domain, $isSitemap)) != false) {
                $pages = array_merge($pages, $subPages);
            }
        }

        return $pages;
    }

    /**
     * Check if page should be added to the sitemap.
     *
     * @param PageModel $page      Page model.
     * @param bool      $isSitemap Is sitemap.
     * @param int       $time      Current time stamp.
     *
     * @return bool
     */
    private function shouldPageBeAdded(PageModel $page, $isSitemap, $time)
    {
        // Only handle i18n pages.
        if ($page->type !== 'i18n_regular' || !$this->isPublished($page, $time)) {
            return false;
        }

        if ($isSitemap) {
            return $this->shouldBeAddedToSitemap($page);
        }

        // Do not add page if page is excluded from search.
        if ($page->noSearch) {
            return false;
        }

        // Do not add if page is protected
        if ($page->protected && ! $this->config->get('indexProtected')) {
            return false;
        }

        return true;
    }

    /**
     * Check if page should be added to the sitemap.
     *
     * @param PageModel $page Page model.
     *
     * @return bool
     */
    private function shouldBeAddedToSitemap(PageModel $page)
    {
        if ($page->protected) {
            return $page->sitemap === 'map_always';
        }

        return $page->sitemap !== 'map_never';
    }

    /**
     * Check if page is published.
     *
     * @param PageModel $page Page model.
     * @param int       $time Current timestamp.
     *
     * @return bool
     */
    private function isPublished(PageModel $page, $time)
    {
        if (!$page->published) {
            return false;
        }

        return ($page->start == '' || $page->start <= $time) && ($page->stop == '' || $page->stop > ($time + 60));
    }

    /**
     * Create model from the result.
     *
     * @param array $result Database result.
     *
     * @return PageModel
     */
    private function createModel($result)
    {
        $page = $this->registry->fetch('tl_page', $result['id']);

        if ($page === null) {
            $page = new PageModel();
            $page->setRow($result);

            $this->registry->register($page);
        }

        return $page;
    }

    /**
     * Check if page should be indexed.
     *
     * @param PageModel $page The page model.
     *
     * @return bool
     */
    private function shouldBeIndexed($page): bool
    {
        return (!$page->protected || $this->config->get('indexProtected'));
    }
}
