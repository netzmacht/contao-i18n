<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use function array_merge;
use function array_unique;
use function array_values;

abstract class AbstractSearchableUrlsListener
{
    /**
     * Build the sitemap and searchable pages list.
     *
     * @param list<string>            $pages      Page array.
     * @param int|numeric-string|null $rootPageId Root page.
     * @param bool                    $isSitemap  Collect pages for the sitemap.
     *
     * @return list<string>
     */
    public function onGetSearchablePages(
        array $pages,
        int|string|null $rootPageId = null,
        bool $isSitemap = false,
    ): array {
        $pages = array_merge(
            $pages,
            $this->collectPages((int) $rootPageId ?: 0, '', $isSitemap),
        );

        $pages = array_values(
            array_unique($pages),
        );

        return $pages;
    }

    /**
     * Get all searchable i18n pages and return them as array.
     *
     * @see https://github.com/contao/core/blob/master/system/modules/core/classes/Backend.php
     *
     * @param int    $pid       Parent id.
     * @param string $domain    Domain name.
     * @param bool   $isSitemap Fetch for the sitemap Sitemap.
     *
     * @return list<string>
     */
    abstract protected function collectPages(int $pid = 0, string $domain = '', bool $isSitemap = false): array;
}
