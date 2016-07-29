<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\I18n\SearchIndex;

use Database\Result;
use Model\Registry;
use Netzmacht\Contao\Toolkit\ServiceContainerTrait;

/**
 * SitemapBuilder adds i18n_regular pages to the sitemap.
 *
 * @package Netzmacht\Contao\I18n\SearchIndex
 */
class SitemapBuilder
{
    use ServiceContainerTrait;

    /**
     * Database connection.
     *
     * @var \Database
     */
    private $database;

    /**
     * Model registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->database = $this->getServiceContainer()->getDatabaseConnection();
        $this->registry = Registry::getInstance();
    }

    /**
     * Build the sitemap and searchable pages list.
     *
     * @param array $pages      Page array.
     * @param null  $rootPageId Root page.
     * @param bool  $isSitemap  Collect pages for the sitemap.
     *
     * @return array
     */
    public function build($pages, $rootPageId = null, $isSitemap = false)
    {
        return array_merge(
            $pages,
            $this->collectPages($rootPageId ?: 0, '', $isSitemap)
        );
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
    private function collectPages($pid = 0, $domain = '', $isSitemap = false)
    {
        $time  = \Date::floorToMinute();
        $query = <<<SQL
  SELECT * 
    FROM tl_page 
   WHERE pid=? 
     AND (start='' OR start<='$time') 
     AND (stop='' OR stop>'" . ($time + 60) . "') 
     AND published='1' 
ORDER BY sorting
SQL;

        // Get published pages
        $result = $this->database->prepare($query)->execute($pid);
        $pages  = array();

        // Recursively walk through all subpages
        while ($result->next()) {
            $page = $this->createModel($result);

            if ($this->shouldPageBeAdded($page, $isSitemap, $time)) {
                $pages[] = $page->getAbsoluteUrl();

                // Get articles with teaser
                $query = <<<SQL
  SELECT * 
    FROM tl_article 
   WHERE pid=? 
     AND (start='' OR start<='$time') 
     AND (stop='' OR stop>'" . ($time + 60) . "') 
     AND published='1' 
     AND showTeaser='1' 
ORDER BY sorting
SQL;

                $articles = $this->database->prepare($query)->execute($result->id);
                while ($articles->next()) {
                    $pages[] = sprintf(
                        $page->getAbsoluteUrl('/articles/%s'),
                        (($articles->alias != '' && !\Config::get('disableAlias'))
                            ? $articles->alias
                            : $articles->id
                        )
                    );
                }
            }

            // Get subpages
            if ((!$page->protected || \Config::get('indexProtected'))
                && ($subPages = $this::collectPages($page->id, $domain, $isSitemap)) != false) {
                $pages = array_merge($pages, $subPages);
            }
        }

        return $pages;
    }

    /**
     * Check if page should be added to the sitemap.
     *
     * @param \PageModel $page      Page model.
     * @param bool       $isSitemap Is sitemap.
     * @param int        $time      Current time stamp.
     *
     * @return bool
     */
    private function shouldPageBeAdded(\PageModel $page, $isSitemap, $time)
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
        if ($page->protected && ! \Config::get('indexProtected')) {
            return false;
        }

        return true;
    }

    /**
     * Check if page should be added to the sitemap.
     *
     * @param \PageModel $page Page model.
     *
     * @return bool
     */
    private function shouldBeAddedToSitemap(\PageModel $page)
    {
        if ($page->protected) {
            return $page->sitemap === 'map_always';
        }

        return $page->sitemap !== 'map_never';
    }

    /**
     * Check if page is published.
     *
     * @param \PageModel $page Page model.
     * @param int        $time Current timestamp.
     *
     * @return bool
     */
    private function isPublished(\PageModel $page, $time)
    {
        if (!$page->published) {
            return false;
        }

        return ($page->start == '' || $page->start <= $time) && ($page->stop == '' || $page->stop > ($time + 60));
    }

    /**
     * Create model from the result.
     *
     * @param Result $result Database result.
     *
     * @return \PageModel
     */
    private function createModel($result)
    {
        $page = $this->registry->fetch('tl_page', $result->id);

        if ($page === null) {
            $page = new \PageModel($result);

            return $page;
        }

        return $page;
    }
}
