<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Model;
use Contao\Model\Collection;
use Contao\PageModel;
use Generator;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Override;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function assert;

/** @extends ContentSitemapListener<FaqCategoryModel> */
#[AsEventListener]
final class I18nFaqSitemapListener extends ContentSitemapListener
{
    public function __construct(
        private readonly RepositoryManager $repositoryManager,
        private readonly I18nPageRepository $i18nPageRepository,
        ContextStack $contextStack,
        ContentUrlGenerator $urlGenerator,
    ) {
        parent::__construct($contextStack, $urlGenerator);
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
            foreach ($this->progressTranslations($translations, $processed, $time, $faqCategory, $rootIds) as $url) {
                yield $url;
            }
        }
    }

    /** {@inheritDoc} */
    #[Override]
    protected function processTranslation(
        Model $parent,
        PageModel $translation,
        array &$processed,
        int $time,
    ): Generator {
        if (! $this->shouldBeProcessed($processed, $time, $translation, $parent->jumpTo)) {
            return;
        }

        // Get the items
        $faqRepository = $this->repositoryManager->getRepository(FaqModel::class);
        assert($faqRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $items = $faqRepository->findPublishedByPid($parent->id);
        if (! $items instanceof Collection) {
            return;
        }

        foreach ($items as $faq) {
            yield $this->urlGenerator->generate($faq, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
