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

/**
 * Class AbstractSearchableUrlsListener
 *
 * @package Netzmacht\Contao\I18n\EventListener
 */
abstract class AbstractSearchableUrlsListener
{

    /**
     * Build the sitemap and searchable pages list.
     *
     * @param array $pages      Page array.
     * @param null  $rootPageId Root page.
     * @param bool  $isSitemap  Collect pages for the sitemap.
     *
     * @return array
     */
    public function onGetSearchablePages(array $pages, $rootPageId = null, $isSitemap = false): array
    {
        $pages = array_merge(
            $pages,
            $this->collectPages((int) $rootPageId ?: 0, '', $isSitemap)
        );

        $pages = array_values(
            array_unique($pages)
        );

        return $pages;
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
    abstract protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array;
}
