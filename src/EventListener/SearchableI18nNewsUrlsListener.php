<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\Model\Collection;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Netzmacht\Contao\I18n\Model\Article\TranslatedArticleFinder;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function assert;
use function in_array;
use function preg_replace;
use function sprintf;

final class SearchableI18nNewsUrlsListener extends AbstractContentSearchableUrlsListener
{
    /**
     * Model repository manager.
     */
    private RepositoryManager $repositoryManager;

    private I18nPageRepository $i18n;

    /**
     * Contao config adapter.
     *
     * @var Adapter<Config>
     */
    private Adapter $config;

    /**
     * @param RepositoryManager       $repositoryManager  Model repository manager.
     * @param I18nPageRepository      $i18nPageRepository I18n page repository.
     * @param TranslatedArticleFinder $articleFinder      Translated article finder.
     * @param Database                $database           Legacy contao database connection.
     * @param Adapter<Config>         $config             Contao config adpater.
     */
    public function __construct(
        RepositoryManager $repositoryManager,
        I18nPageRepository $i18nPageRepository,
        private TranslatedArticleFinder $articleFinder,
        Database $database,
        Adapter $config,
    ) {
        parent::__construct($database);

        $this->repositoryManager = $repositoryManager;
        $this->i18n              = $i18nPageRepository;
        $this->config            = $config;
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
        $archiveRepository = $this->repositoryManager->getRepository(NewsArchiveModel::class);
        assert($archiveRepository instanceof ContaoRepository);
        $collection = $archiveRepository->findByProtected('');

        // Walk through each archive
        if ($collection !== null) {
            while ($collection->next()) {
                // Skip news archives without target page
                if (! $collection->jumpTo) {
                    continue;
                }

                $translations = $this->i18n->getPageTranslations($collection->jumpTo);

                foreach ($translations as $translation) {
                    // Skip news archives outside the root nodes
                    if (
                        (! empty($root) && ! in_array($translation->id, $root))
                        || $translation->type !== 'i18n_regular'
                    ) {
                        continue;
                    }

                    $pages = $this->processTranslation(
                        $collection->current(),
                        $translation,
                        $pages,
                        $isSitemap,
                        $processed,
                        $time,
                    );
                }
            }
        }

        return $pages;
    }

    /**
     * Process a page translation.
     *
     * @param NewsArchiveModel                           $newsArchiveModel The page.
     * @param PageModel                                  $translation      The page translation.
     * @param list<string>                               $pages            List of all added pages.
     * @param bool                                       $isSitemap        If true the sitemap is generated.
     * @param array<int|string,array<int|string,string>> $processed        Cache of processed pages.
     * @param int                                        $time             Start time.
     *
     * @return list<string>
     */
    private function processTranslation(
        NewsArchiveModel $newsArchiveModel,
        PageModel $translation,
        array $pages,
        bool $isSitemap,
        array &$processed,
        int $time,
    ): array {
        // Get the URL of the jumpTo page
        if (! isset($processed[$newsArchiveModel->jumpTo][$translation->id])) {
            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return $pages;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation, $isSitemap)) {
                return $pages;
            }

            // Generate the URL
            $processed[$newsArchiveModel->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                $this->config->get('useAutoItem') ? '/%s' : '/items/%s',
            );
        }

        $url = $processed[$newsArchiveModel->jumpTo][$translation->id];

        // Get the items
        $newsRepository = $this->repositoryManager->getRepository(NewsModel::class);
        assert($newsRepository instanceof ContaoRepository);
        $collection = $newsRepository->findPublishedDefaultByPid($newsArchiveModel->id);

        if ($collection instanceof Collection) {
            foreach ($collection as $newsModel) {
                assert($newsModel instanceof NewsModel);
                $pages[] = $this->getLink($newsModel, $url);
            }
        }

        return $pages;
    }

    protected function getLink(NewsModel $newsModel, string $url): string
    {
        switch ($newsModel->source) {
            // Link to an external page
            case 'external':
                return $newsModel->url;

            // Link to an internal page
            case 'internal':
                $target = $this->i18n->getTranslatedPage($newsModel->jumpTo);
                if ($target instanceof PageModel) {
                    return $target->getAbsoluteUrl();
                }

                break;

            // Link to an article
            case 'article':
                $repository   = $this->repositoryManager->getRepository(ArticleModel::class);
                $articleModel = $repository->find((int) $newsModel->articleId);

                if ($articleModel === null) {
                    break;
                }

                $parentPage = $this->i18n->getTranslatedPage($articleModel->pid);
                if ($parentPage instanceof PageModel) {
                    $articleModel = $this->getTranslatedArticle($parentPage, $articleModel);

                    return StringUtil::ampersand(
                        $parentPage->getAbsoluteUrl('/articles/' . ($articleModel->alias ?: $articleModel->id)),
                    );
                }

                break;

            default:
                // Do nothing.
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $url), ($newsModel->alias ?: $newsModel->id));
    }

    /**
     * Get the translation of an article. If the article is not a translated article, return the passed article.
     */
    private function getTranslatedArticle(PageModel $pageModel, ArticleModel $articleModel): ArticleModel
    {
        // Replace article with the translation
        if ($pageModel->type === 'i18n_regular') {
            $translated = $this->articleFinder->getOverrides($pageModel);
            if (isset($translated[$articleModel->id])) {
                $articleModel = $translated[$articleModel->id];
            }
        }

        return $articleModel;
    }
}
