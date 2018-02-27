<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;

/**
 * Class SearchableI18nNewsUrlsListener
 */
class SearchableI18nNewsUrlsListener extends AbstractSearchableUrlsListener
{
    /**
     * I18n page repository.
     *
     * @var I18nPageRepository
     */
    private $i18n;

    /**
     * Legacy contao database connection.
     *
     * @var Database
     */
    private $database;

    /**
     * Contao config adapter.
     *
     * @var Config|Adapter
     */
    private $config;

    /**
     * SearchableI18nNewsUrlsListener constructor.
     *
     * @param I18nPageRepository $i18nPageRepository I18n page repository.
     * @param Database           $database           Legacy contao database connection.
     * @param Config|Adapter     $config             Contao config adpater.
     */
    public function __construct(I18nPageRepository $i18nPageRepository, Database $database, $config)
    {
        $this->i18n     = $i18nPageRepository;
        $this->database = $database;
        $this->config   = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array
    {
        $root      = [];
        $processed = [];
        $time      = Date::floorToMinute();
        $pages     = [];

        if ($pid > 0) {
            $root = $this->database->getChildRecords($pid, 'tl_page');
        }

        // Get all news archives
        $collection = NewsArchiveModel::findByProtected('');

        // Walk through each archive
        if ($collection !== null) {
            while ($collection->next()) {
                // Skip news archives without target page
                if (!$collection->jumpTo) {
                    continue;
                }

                $translations = $this->i18n->getPageTranslations($collection->jumpTo);

                foreach ($translations as $translation) {
                    // Skip news archives outside the root nodes
                    if (!empty($root) && !\in_array($translation->id, $root)) {
                        continue;
                    }

                    // Get the URL of the jumpTo page
                    if (!isset($processed[$collection->jumpTo][$translation->id])) {
                         // The target page has not been published (see #5520)
                        if (!$translation->published
                            || ($translation->start != '' && $translation->start > $time)
                            || ($translation->stop != '' && $translation->stop <= ($time + 60))
                        ) {
                            continue;
                        }

                        if ($isSitemap) {
                            // The target page is protected (see #8416)
                            if ($translation->protected) {
                                continue;
                            }

                            // The target page is exempt from the sitemap (see #6418)
                            if ($translation->sitemap == 'map_never') {
                                continue;
                            }
                        }

                        // Generate the URL
                        $processed[$collection->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                            $this->config->get('useAutoItem') ? '/%s' : '/items/%s'
                        );
                    }

                    $strUrl = $processed[$collection->jumpTo][$translation->id];

                    // Get the items
                    $objArticle = \NewsModel::findPublishedDefaultByPid($collection->id);

                    if ($objArticle !== null) {
                        while ($objArticle->next()) {
                            $pages[] = $this->getLink($objArticle, $strUrl);
                        }
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * Return the link of a news article
     *
     * @param NewsModel $objItem
     * @param string    $strUrl
     * @param string    $strBase
     *
     * @return string
     */
    protected function getLink($objItem, $strUrl, $strBase='')
    {
        switch ($objItem->source)
        {
            // Link to an external page
            case 'external':
                return $objItem->url;
                break;

            // Link to an internal page
            case 'internal':
                if (($target = $this->i18n->getTranslatedPage($objItem->jumpTo)) instanceof PageModel) {
                    /** @var PageModel $target */
                    return $target->getAbsoluteUrl();
                }
                break;

            // Link to an article
            case 'article':
                if (($articleModel = ArticleModel::findByPk($objItem->articleId, array('eager'=>true))) !== null
                    && ($objPid = $this->i18n->getTranslatedPage($articleModel->pid)) instanceof PageModel)
                {
                    /** @var PageModel $objPid */
                    return ampersand(
                        $objPid->getAbsoluteUrl('/articles/' . ($articleModel->alias ?: $articleModel->id))
                    );
                }
                break;
        }

        // Backwards compatibility (see #8329)
        if ($strBase != '' && !preg_match('#^https?://#', $strUrl)) {
            $strUrl = $strBase . $strUrl;
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
    }
}
