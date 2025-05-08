<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
use Contao\Model;
use Contao\Model\Collection;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
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

/** @extends ContentSitemapListener<NewsArchiveModel> */
#[AsEventListener]
final class I18nNewsSitemapListener extends ContentSitemapListener
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

        // Get all news archives
        $archiveRepository = $this->repositoryManager->getRepository(NewsArchiveModel::class);
        assert($archiveRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $collection = $archiveRepository->findByProtected('');

        // Walk through each archive
        if ($collection === null) {
            return;
        }

        foreach ($collection as $archive) {
            assert($archive instanceof NewsArchiveModel);

            // Skip news archives without a target page
            if (! $archive->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($collection->jumpTo);
            foreach ($this->progressTranslations($translations, $processed, $time, $archive, $rootIds) as $url) {
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
        $newsRepository = $this->repositoryManager->getRepository(NewsModel::class);
        assert($newsRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $collection = $newsRepository->findPublishedDefaultByPid($parent->id);

        if (! $collection instanceof Collection) {
            return;
        }

        foreach ($collection as $newsModel) {
            yield $this->urlGenerator->generate($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
