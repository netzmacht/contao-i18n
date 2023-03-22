<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function in_array;
use function preg_replace;
use function sprintf;

class SearchableI18nFaqUrlsListener extends AbstractContentSearchableUrlsListener
{
    private RepositoryManager $repositoryManager;

    private I18nPageRepository $i18nPageRepository;

    /**
     * Contao config adapter.
     *
     * @var Adapter<Config>
     */
    private Adapter $config;

    /**
     * @param RepositoryManager  $repositoryManager  Model repository manager.
     * @param I18nPageRepository $i18nPageRepository I18n page repository.
     * @param Database           $database           Legacy contao database connection.
     * @param Adapter<Config>    $config             Contao config adapter.
     */
    public function __construct(
        RepositoryManager $repositoryManager,
        I18nPageRepository $i18nPageRepository,
        Database $database,
        Adapter $config
    ) {
        parent::__construct($database);

        $this->repositoryManager  = $repositoryManager;
        $this->i18nPageRepository = $i18nPageRepository;
        $this->config             = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array
    {
        $pages     = [];
        $root      = $this->getPageChildRecords($pid);
        $processed = [];
        $time      = Date::floorToMinute();

        // Get all categories
        $categoryRepository = $this->repositoryManager->getRepository(FaqCategoryModel::class);
        $collection         = $categoryRepository->findAll();

        // Walk through each category
        if ($collection === null) {
            return $pages;
        }

        while ($collection->next()) {
            // Skip FAQs without target page
            if (! $collection->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($collection->jumpTo);

            foreach ($translations as $translation) {
                // Skip FAQs outside the root nodes
                if ((! empty($root) && ! in_array($translation->id, $root)) || $translation->type !== 'i18n_regular') {
                    continue;
                }

                $pages = $this->processTranslation(
                    $collection->current(),
                    $translation,
                    $pages,
                    $processed,
                    $isSitemap,
                    $time
                );
            }
        }

        return $pages;
    }

    /**
     * Process the translation.
     *
     * @param FaqCategoryModel                           $category    The faq category.
     * @param PageModel                                  $translation The translation.
     * @param list<string>                               $pages       List of page urls.
     * @param array<int|string,array<int|string,string>> $processed   Cache of processed paged.
     * @param bool                                       $isSitemap   Sitemap.
     * @param int                                        $time        The time.
     *
     * @return list<string>
     */
    private function processTranslation(
        FaqCategoryModel $category,
        PageModel $translation,
        array $pages,
        array &$processed,
        bool $isSitemap,
        int $time
    ): array {
        // Get the URL of the jumpTo page
        if (! isset($processed[$category->jumpTo][$translation->id])) {
            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return $pages;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation, $isSitemap)) {
                return $pages;
            }

            // Generate the URL
            $processed[$category->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                $this->config->get('useAutoItem') ? '/%s' : '/items/%s'
            );
        }

        // Get the items
        $faqRepository = $this->repositoryManager->getRepository(FaqModel::class);
        $items         = $faqRepository->findPublishedByPid($category->id);
        $url           = $processed[$category->jumpTo][$translation->id];

        if ($items !== null) {
            while ($items->next()) {
                $pages[] = sprintf(
                    preg_replace('/%(?!s)/', '%%', $url),
                    ($items->alias ?: $items->id)
                );
            }
        }

        return $pages;
    }
}
