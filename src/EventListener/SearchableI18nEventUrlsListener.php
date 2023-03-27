<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\Model\Collection;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function assert;
use function in_array;
use function preg_replace;
use function sprintf;

final class SearchableI18nEventUrlsListener extends AbstractContentSearchableUrlsListener
{
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
     * @param Adapter<Config>    $config             Contao config adpater.
     */
    public function __construct(
        private RepositoryManager $repositoryManager,
        private I18nPageRepository $i18nPageRepository,
        Database $database,
        Adapter $config,
    ) {
        parent::__construct($database);

        $this->config = $config;
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

        // Get all calendars
        $calendarRepository = $this->repositoryManager->getRepository(CalendarModel::class);
        assert($calendarRepository instanceof ContaoRepository);
        $collection = $calendarRepository->findByProtected('');

        // Walk through each calendar
        if (! $collection instanceof Collection) {
            return $pages;
        }

        foreach ($collection as $calendar) {
            assert($calendar instanceof CalendarModel);

            // Skip calendars without target page
            if (! $calendar->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($calendar->jumpTo);

            foreach ($translations as $translation) {
                // Skip calendars outside the root nodes
                if ((! empty($root) && ! in_array($translation->id, $root)) || $translation->type !== 'i18n_regular') {
                    continue;
                }

                $pages = $this->processTranslation(
                    $calendar,
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
     * @param CalendarModel                              $calendar    The calendar.
     * @param PageModel                                  $translation The translation.
     * @param list<string>                               $pages       List of page urls.
     * @param array<int|string,array<int|string,string>> $processed   Cache of processed paged.
     * @param bool                                       $isSitemap   Sitemap.
     * @param int                                        $time        The time.
     *
     * @return list<string>
     */
    private function processTranslation(
        CalendarModel $calendar,
        PageModel $translation,
        array $pages,
        array &$processed,
        bool $isSitemap,
        int $time,
    ): array {
        // Get the URL of the jumpTo page
        if (! isset($processed[$calendar->jumpTo][$translation->id])) {
            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return $pages;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation, $isSitemap)) {
                return $pages;
            }

            // Generate the URL
            $processed[$calendar->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                $this->config->get('useAutoItem') ? '/%s' : '/events/%s',
            );
        }

        $strUrl = $processed[$calendar->jumpTo][$translation->id];

        // Get the items
        $eventsRepository = $this->repositoryManager->getRepository(CalendarEventsModel::class);
        assert($eventsRepository instanceof ContaoRepository);
        $objEvents = $eventsRepository->findPublishedDefaultByPid($calendar->id);

        if ($objEvents !== null) {
            while ($objEvents->next()) {
                $pages[] = sprintf(
                    preg_replace('/%(?!s)/', '%%', $strUrl),
                    ($objEvents->alias ?: $objEvents->id),
                );
            }
        }

        return $pages;
    }
}
