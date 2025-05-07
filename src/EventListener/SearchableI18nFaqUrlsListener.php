<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Override;

use function assert;
use function in_array;
use function preg_replace;
use function sprintf;

final class SearchableI18nFaqUrlsListener extends AbstractContentSearchableUrlsListener
{
    /**
     * @param RepositoryManager  $repositoryManager  Model repository manager.
     * @param I18nPageRepository $i18nPageRepository I18n page repository.
     * @param Database           $database           Legacy contao database connection.
     * @param Adapter<Config>    $config             Contao config adapter.
     */
    public function __construct(
        private readonly RepositoryManager $repositoryManager,
        private readonly I18nPageRepository $i18nPageRepository,
        Database $database,
        private readonly Adapter $config,
    ) {
        parent::__construct($database);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
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
        if (! $collection instanceof Collection) {
            return $pages;
        }

        foreach ($collection as $faqCategory) {
            assert($faqCategory instanceof FaqCategoryModel);

            // Skip FAQs without target page
            if (! $faqCategory->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($faqCategory->jumpTo);

            foreach ($translations as $translation) {
                // Skip FAQs outside the root nodes
                if ((! empty($root) && ! in_array($translation->id, $root)) || $translation->type !== 'i18n_regular') {
                    continue;
                }

                $pages = $this->processTranslation(
                    $faqCategory,
                    $translation,
                    $pages,
                    $processed,
                    $isSitemap,
                    $time,
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
        int $time,
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
                $this->config->get('useAutoItem') ? '/%s' : '/items/%s',
            );
        }

        // Get the items
        $faqRepository = $this->repositoryManager->getRepository(FaqModel::class);
        assert($faqRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $items = $faqRepository->findPublishedByPid($category->id);
        $url   = $processed[$category->jumpTo][$translation->id];

        if ($items instanceof Collection) {
            foreach ($items as $faq) {
                assert($faq instanceof FaqModel);

                $pages[] = sprintf(
                    (string) preg_replace('/%(?!s)/', '%%', $url),
                    ($faq->alias ?: $faq->id),
                );
            }
        }

        return $pages;
    }
}
