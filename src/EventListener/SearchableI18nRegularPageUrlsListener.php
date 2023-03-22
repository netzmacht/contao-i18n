<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Date;
use Contao\Model\Registry;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\I18n\Model\Article\PageArticlesWithTeasersQuery;
use Netzmacht\Contao\I18n\Model\Page\PublishedI18nRegularPagesQuery;
use RuntimeException;

use function array_merge;
use function get_class;
use function sprintf;

class SearchableI18nRegularPageUrlsListener extends AbstractSearchableUrlsListener
{
    private Connection $connection;

    /**
     * Model registry.
     */
    private Registry $registry;

    /**
     * Contao config adapter.
     *
     * @var Adapter<Config>
     */
    private Adapter $config;

    /**
     * Construct.
     *
     * @param Connection      $connection Database connection.
     * @param Registry        $registry   Model registry.
     * @param Adapter<Config> $config     Contao config adapter.
     */
    public function __construct(Connection $connection, Registry $registry, Adapter $config)
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
     * @see https://github.com/contao/core/blob/master/system/modules/core/classes/Backend.php
     *
     * @param int    $pid       Parent id.
     * @param string $domain    Domain name.
     * @param bool   $isSitemap Fetch for the sitemap Sitemap.
     *
     * @return list<string>
     */
    protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array
    {
        $time   = Date::floorToMinute();
        $query  = new PublishedI18nRegularPagesQuery($this->connection);
        $result = $query->execute($time, $pid);

        // Get published pages
        $pages = [];

        // Recursively walk through all subpages
        while ($pageResult = $result->fetchAssociative()) {
            $page = $this->createModel($pageResult);

            if ($this->shouldPageBeAdded($page, $isSitemap, $time)) {
                $pages[] = $page->getAbsoluteUrl();

                // Get articles with teaser
                $query    = new PageArticlesWithTeasersQuery($this->connection);
                $articles = $query->execute((int) $pageResult['id'], $time);

                while ($article = $articles->fetchAssociative()) {
                    // Do not show pages without a translation. They are ignored.
                    if ($article['languageMain'] > 0) {
                        continue;
                    }

                    $pages[] = sprintf(
                        $page->getAbsoluteUrl('/articles/%s'),
                        ($article['alias'] !== '' && ! $this->config->get('disableAlias')
                            ? $article['alias']
                            : $article['id']
                        )
                    );
                }
            }

            // Get subpages
            if (! $this->shouldBeIndexed($page)) {
                continue;
            }

            $subPages = $this->collectPages((int) $page->id, $domain, $isSitemap);
            if ($subPages === []) {
                continue;
            }

            $pages = array_merge($pages, $subPages);
        }

        return $pages;
    }

    /**
     * Check if page should be added to the sitemap.
     *
     * @param PageModel $page      Page model.
     * @param bool      $isSitemap Is sitemap.
     * @param int       $time      Current time stamp.
     */
    private function shouldPageBeAdded(PageModel $page, $isSitemap, int $time): bool
    {
        // Only handle i18n pages.
        if ($page->type !== 'i18n_regular' || ! $this->isPublished($page, $time)) {
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
        return ! $page->protected || $this->config->get('indexProtected');
    }

    private function shouldBeAddedToSitemap(PageModel $page): bool
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
     */
    private function isPublished(PageModel $page, int $time): bool
    {
        if (! $page->published) {
            return false;
        }

        return ($page->start === '' || $page->start <= $time) && ($page->stop === '' || $page->stop > $time + 60);
    }

    /**
     * Create model from the result.
     *
     * @param array<string,mixed> $result Database result.
     */
    private function createModel(array $result): PageModel
    {
        $page = $this->registry->fetch('tl_page', $result['id']);

        if ($page === null) {
            $page = new PageModel();
            $page->setRow($result);

            $this->registry->register($page);
        }

        if (! $page instanceof PageModel) {
            throw new RuntimeException(
                'Unexpected model found. Expected \Contao\PageModel but got ' . get_class($page)
            );
        }

        return $page;
    }

    private function shouldBeIndexed(PageModel $page): bool
    {
        return ! $page->protected || $this->config->get('indexProtected');
    }
}
