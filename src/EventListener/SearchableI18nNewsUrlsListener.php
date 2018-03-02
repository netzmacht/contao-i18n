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

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Article\TranslatedArticleFinder;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\Repository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

/**
 * Class SearchableI18nNewsUrlsListener
 */
class SearchableI18nNewsUrlsListener extends AbstractContentSearchableUrlsListener
{
    /**
     * Model repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * I18n page repository.
     *
     * @var I18nPageRepository
     */
    private $i18n;

    /**
     * Contao config adapter.
     *
     * @var Config|Adapter
     */
    private $config;
    /**
     * @var TranslatedArticleFinder
     */
    private $articleFinder;

    /**
     * SearchableI18nNewsUrlsListener constructor.
     *
     * @param RepositoryManager       $repositoryManager  Model repository manager.
     * @param I18nPageRepository      $i18nPageRepository I18n page repository.
     * @param TranslatedArticleFinder $articleFinder      Translated article finder.
     * @param Database                $database           Legacy contao database connection.
     * @param Config|Adapter          $config             Contao config adpater.
     */
    public function __construct(
        RepositoryManager $repositoryManager,
        I18nPageRepository $i18nPageRepository,
        TranslatedArticleFinder $articleFinder,
        Database $database,
        $config
    ) {
        parent::__construct($database);

        $this->repositoryManager = $repositoryManager;
        $this->i18n              = $i18nPageRepository;
        $this->config            = $config;
        $this->articleFinder     = $articleFinder;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array
    {
        $root      = $this->getPageChildRecords($pid);
        $processed = [];
        $time      = Date::floorToMinute();
        $pages     = [];

        // Get all news archives
        /** @var NewsArchiveModel|Repository $archiveRepository */
        $archiveRepository = $this->repositoryManager->getRepository(NewsArchiveModel::class);
        $collection        = $archiveRepository->findByProtected('');

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
                    if (!empty($root) && !\in_array($translation->id, $root) || $translation->type !== 'i18n_regular') {
                        continue;
                    }

                    $pages = $this->processTranslation(
                        $collection->current(),
                        $translation,
                        $pages,
                        $isSitemap,
                        $processed,
                        $time
                    );
                }
            }
        }

        return $pages;
    }


    /**
     * Process a page translation.
     *
     * @param NewsArchiveModel $newsArchiveModel The page.
     * @param PageModel        $translation      The page translation.
     * @param array            $pages            List of all added pages.
     * @param bool             $isSitemap        If true the sitemap is generated.
     * @param array            $processed        Cache of processed pages.
     * @param int              $time             Start time.
     *
     * @return array
     */
    private function processTranslation(
        NewsArchiveModel $newsArchiveModel,
        PageModel $translation,
        array $pages,
        bool $isSitemap,
        array &$processed,
        int $time
    ): array {
        // Get the URL of the jumpTo page
        if (!isset($processed[$newsArchiveModel->jumpTo][$translation->id])) {
            // The target page has not been published (see #5520)
            if (!$this->isPagePublished($translation, $time)) {
                return $pages;
            }

            if (!$this->shouldPageBeAddedToSitemap($translation, $isSitemap)) {
                return $pages;
            }

            // Generate the URL
            $processed[$newsArchiveModel->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                $this->config->get('useAutoItem') ? '/%s' : '/items/%s'
            );
        }

        $url = $processed[$newsArchiveModel->jumpTo][$translation->id];

        // Get the items
        /** @var NewsModel|Repository $newsRepository */
        $newsRepository = $this->repositoryManager->getRepository(NewsModel::class);
        $objArticle     = $newsRepository->findPublishedDefaultByPid($newsArchiveModel->id);

        if ($objArticle !== null) {
            while ($objArticle->next()) {
                $pages[] = $this->getLink($objArticle, $url);
            }
        }

        return $pages;
    }

    /**
     * Return the link of a news article
     *
     * @param NewsModel $newsModel The news model.
     * @param string    $url       The given url.
     *
     * @return string
     */
    protected function getLink($newsModel, $url)
    {
        switch ($newsModel->source) {
            // Link to an external page
            case 'external':
                return $newsModel->url;

            // Link to an internal page
            case 'internal':
                if (($target = $this->i18n->getTranslatedPage($newsModel->jumpTo)) instanceof PageModel) {
                    /** @var PageModel $target */
                    return $target->getAbsoluteUrl();
                }
                break;

            // Link to an article
            case 'article':
                /** @var ArticleModel|Repository $repository */
                $repository   = $this->repositoryManager->getRepository(ArticleModel::class);
                $articleModel = $articleModel = $repository->findByPK((int) $newsModel->articleId, ['eager' => true]);

                if ($articleModel !== null
                    && ($objPid = $this->i18n->getTranslatedPage($articleModel->pid)) instanceof PageModel
                ) {
                    // Replace article with the
                    if ($objPid->type === 'i18n_regular') {
                        $translated = $this->articleFinder->getOverrides($objPid);
                        if (isset($translated[$articleModel->id])) {
                            $articleModel = $translated[$articleModel->id];
                        }
                    }

                    /** @var PageModel $objPid */
                    return ampersand(
                        $objPid->getAbsoluteUrl('/articles/' . ($articleModel->alias ?: $articleModel->id))
                    );
                }
                break;

            default:
                // Do nothing.
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $url), ($newsModel->alias ?: $newsModel->id));
    }
}
