<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Date;
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
final class I18nEventSitemapListener extends ContentSitemapListener
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

            foreach ($translations as $language => $translation) {
                $context = LocaleContext::ofLocale($language);
                $this->contextStack->enterContext($context);
                $this->urlGenerator->reset();

                // Skip calendars outside the root nodes
                if (! in_array($translation->hofff_root_page_id, $rootIds) || $translation->type !== 'i18n_regular') {
                    $this->contextStack->leaveContext($context);
                    continue;
                }

                foreach ($this->processTranslation($calendar, $translation, $processed, $time) as $url) {
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
     * @param CalendarModel              $calendar    The calendar.
     * @param PageModel                  $translation The translation.
     * @param array<int,array<int,bool>> $processed   Cache of processed paged.
     * @param int                        $time        The time.
     *
     * @return Generator<string>
     */
    private function processTranslation(
        CalendarModel $calendar,
        PageModel $translation,
        array &$processed,
        int $time,
    ): Generator {
        // Get the URL of the jumpTo page
        if (! isset($processed[$calendar->jumpTo][$translation->id])) {
            $processed[$calendar->jumpTo][(int) $translation->id] = false;

            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation)) {
                return;
            }

            // Generate the URL
            $processed[$calendar->jumpTo][(int) $translation->id] = true;
        } elseif (! $processed[$calendar->jumpTo][$translation->id]) {
            return;
        }

        // Get the items
        $eventsRepository = $this->repositoryManager->getRepository(CalendarEventsModel::class);
        assert($eventsRepository instanceof ContaoRepository);
        /** @psalm-suppress UndefinedMagicMethod */
        $objEvents = $eventsRepository->findPublishedDefaultByPid($calendar->id);
        if ($objEvents === null) {
            return;
        }

        foreach ($objEvents as $event) {
            yield $this->urlGenerator->generate($event, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
