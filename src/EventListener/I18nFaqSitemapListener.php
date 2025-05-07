<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Generator;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\LocaleContext;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Override;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function assert;
use function in_array;

#[AsEventListener]
final class I18nFaqSitemapListener extends ContentSitemapListener
{
    public function __construct(
        private readonly RepositoryManager $repositoryManager,
        private readonly I18nPageRepository $i18nPageRepository,
        private readonly ContextStack $contextStack,
        ContentUrlGenerator $urlGenerator,
    ) {
        parent::__construct($urlGenerator);
    }

    /** {@inheritDoc} */
    #[Override]
    protected function collectPages(array $rootIds): Generator
    {
        $processed = [];
        $time      = Date::floorToMinute();

        // Get all categories
        $categoryRepository = $this->repositoryManager->getRepository(FaqCategoryModel::class);
        $collection         = $categoryRepository->findAll();

        // Walk through each category
        if (! $collection instanceof Collection) {
            return;
        }

        foreach ($collection as $faqCategory) {
            assert($faqCategory instanceof FaqCategoryModel);

            // Skip FAQs without a target page
            if (! $faqCategory->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($faqCategory->jumpTo);

            foreach ($translations as $language => $translation) {
                $context = LocaleContext::ofLocale($language);
                $this->contextStack->enterContext($context);
                $this->urlGenerator->reset();

                // Skip FAQs outside the root nodes
                if (
                    (! empty($rootIds) && ! in_array($translation->hofff_root_page_id, $rootIds))
                    || $translation->type !== 'i18n_regular'
                ) {
                    $this->contextStack->leaveContext($context);
                    continue;
                }

                foreach ($this->processTranslation($faqCategory, $translation, $processed, $time) as $url) {
                    yield $url;
                }

                $this->contextStack->leaveContext($context);
            }

            $this->urlGenerator->reset();
        }
    }

    /**
     * Process the translation.
     *
     * @param FaqCategoryModel           $category    The faq category.
     * @param PageModel                  $translation The translation.
     * @param array<int,array<int,bool>> $processed   Cache of processed items.
     * @param int                        $time        The time.
     *
     * @return Generator<string>
     */
    private function processTranslation(
        FaqCategoryModel $category,
        PageModel $translation,
        array &$processed,
        int $time,
    ): Generator {
        // Get the URL of the jumpTo page
        if (! isset($processed[$category->jumpTo][$translation->id])) {
            $processed[$category->jumpTo][(int) $translation->id] = false;

            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation)) {
                return;
            }

            // Generate the URL
            $processed[$category->jumpTo][(int) $translation->id] = true;
        } elseif (! $processed[$category->jumpTo][$translation->id]) {
            return;
        }

        // Get the items
        $faqRepository = $this->repositoryManager->getRepository(FaqModel::class);
        assert($faqRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $items = $faqRepository->findPublishedByPid($category->id);
        if (! $items instanceof Collection) {
            return;
        }

        foreach ($items as $faq) {
            yield $this->urlGenerator->generate($faq, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
