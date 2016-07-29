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
     * @param array $pages      Page array
     * @param null  $rootPageId Root page.
     * @param bool  $isSitemap  Collect pages for the sitemap.
     *
     * @return array
     */
    public function build($pages, $rootPageId=null, $isSitemap=false)
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
     * @see   https://github.com/contao/core/blob/master/system/modules/core/classes/Backend.php
     *
     * @return array
     */
    private function collectPages($pid=0, $domain = '', $isSitemap = false)
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

        if ($result->numRows < 1) {
            return array();
        }

        $pages = array();

        // Recursively walk through all subpages
        while ($result->next()) {
            $page = $this->registry->fetch('tl_page', $result->id);

            if ($page === null) {
                $page = new \PageModel($result);
            }

            if ($page->type == 'i18n_regular') {
                // Searchable and not protected
                if ((!$page->noSearch || $isSitemap)
                    && (!$page->protected
                        || \Config::get('indexProtected')
                        && (!$isSitemap || $page->sitemap == 'map_always')
                    )
                    && (!$isSitemap || $page->sitemap != 'map_never')
                ) {
                    // Published
                    if ($page->published
                        && ($page->start == '' || $page->start <= $time)
                        && ($page->stop == '' || $page->stop > ($time + 60))
                    ) {
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
                        if ($articles->numRows) {
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
                    }
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
}
