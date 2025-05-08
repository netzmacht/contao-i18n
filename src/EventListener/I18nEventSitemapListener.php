<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
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

/** @extends ContentSitemapListener<CalendarModel> */
#[AsEventListener]
final class I18nEventSitemapListener extends ContentSitemapListener
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

        // Get all calendars
        $calendarRepository = $this->repositoryManager->getRepository(CalendarModel::class);
        assert($calendarRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $collection = $calendarRepository->findByProtected('');

        // Walk through each calendar
        if (! $collection instanceof Collection) {
            return;
        }

        foreach ($collection as $calendar) {
            assert($calendar instanceof CalendarModel);

            // Skip calendars without a target page
            if (! $calendar->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($calendar->jumpTo);
            foreach ($this->progressTranslations($translations, $processed, $time, $calendar, $rootIds) as $url) {
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
        $eventsRepository = $this->repositoryManager->getRepository(CalendarEventsModel::class);
        assert($eventsRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $objEvents = $eventsRepository->findPublishedDefaultByPid($parent->id);
        if ($objEvents === null) {
            return;
        }

        foreach ($objEvents as $event) {
            yield $this->urlGenerator->generate($event, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
