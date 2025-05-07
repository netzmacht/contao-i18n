<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
use Contao\Model\Collection;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
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
final class I18nNewsSitemapListener extends ContentSitemapListener
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

        // Get all news archives
        $archiveRepository = $this->repositoryManager->getRepository(NewsArchiveModel::class);
        assert($archiveRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $collection = $archiveRepository->findByProtected('');

        // Walk through each archive
        if ($collection === null) {
            return;
        }

        while ($collection->next()) {
            // Skip news archives without a target page
            if (! $collection->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($collection->jumpTo);

            foreach ($translations as $language => $translation) {
                $context = LocaleContext::ofLocale($language);
                $this->contextStack->enterContext($context);
                $this->urlGenerator->reset();

                // Skip news archives outside the root nodes
                if (
                    (! empty($rootIds) && ! in_array($translation->hofff_root_page_id, $rootIds))
                    || $translation->type !== 'i18n_regular'
                ) {
                    $this->contextStack->leaveContext($context);
                    continue;
                }

                foreach ($this->processTranslation($collection->current(), $translation, $processed, $time) as $url) {
                    yield $url;
                }

                $this->contextStack->leaveContext($context);
            }

            $this->urlGenerator->reset();
        }
    }

    /**
     * Process a page translation.
     *
     * @param NewsArchiveModel           $newsArchiveModel The page.
     * @param PageModel                  $translation      The page translation.
     * @param array<int,array<int,bool>> $processed        Cache of processed items.
     * @param int                        $time             Start time.
     *
     * @return Generator<string>
     */
    private function processTranslation(
        NewsArchiveModel $newsArchiveModel,
        PageModel $translation,
        array &$processed,
        int $time,
    ): Generator {
        // Get the URL of the jumpTo page
        if (! isset($processed[$newsArchiveModel->jumpTo][$translation->id])) {
            $processed[$newsArchiveModel->jumpTo][(int) $translation->id] = false;

            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation)) {
                return;
            }

            // Generate the URL
            $processed[$newsArchiveModel->jumpTo][(int) $translation->id] = true;
        } elseif (! $processed[$newsArchiveModel->jumpTo][$translation->id]) {
            return;
        }

        // Get the items
        $newsRepository = $this->repositoryManager->getRepository(NewsModel::class);
        assert($newsRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $collection = $newsRepository->findPublishedDefaultByPid($newsArchiveModel->id);

        if (! $collection instanceof Collection) {
            return;
        }

        foreach ($collection as $newsModel) {
            yield $this->urlGenerator->generate($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
