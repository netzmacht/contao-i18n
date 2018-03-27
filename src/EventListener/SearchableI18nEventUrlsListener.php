<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    contao-18n
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later https://github.com/netzmacht/contao-i18n/blob/master/LICENSE
 * @filesource
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\Repository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

/**
 * Class SearchableI18nEventUrlsListener
 */
final class SearchableI18nEventUrlsListener extends AbstractContentSearchableUrlsListener
{
    /**
     * I18nPageRepository page repository.
     *
     * @var I18nPageRepository
     */
    private $i18nPageRepository;

    /**
     * Contao config adapter.
     *
     * @var Config|Adapter
     */
    private $config;

    /**
     * Model repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * SearchableI18nEventUrlsListener constructor.
     *
     * @param RepositoryManager  $repositoryManager  Model repository manager.
     * @param I18nPageRepository $i18nPageRepository I18n page repository.
     * @param Database           $database           Legacy contao database connection.
     * @param Config|Adapter     $config             Contao config adpater.
     */
    public function __construct(
        RepositoryManager $repositoryManager,
        I18nPageRepository $i18nPageRepository,
        Database $database,
        $config
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
        $root      = $this->getPageChildRecords($pid);
        $processed = [];
        $time      = Date::floorToMinute();
        $pages     = [];

        // Get all calendars
        /** @var CalendarModel|Repository $calendarRepository */
        $calendarRepository = $this->repositoryManager->getRepository(CalendarModel::class);
        $collection         = $calendarRepository->findByProtected('');

        // Walk through each calendar
        if ($collection == null) {
            return $pages;
        }

        while ($collection->next()) {
            // Skip calendars without target page
            if (!$collection->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($collection->jumpTo);

            foreach ($translations as $translation) {
                // Skip calendars outside the root nodes
                if (!empty($root) && !\in_array($translation->id, $root) || $translation->type !== 'i18n_regular') {
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
     * @param CalendarModel $calendar    The calendar.
     * @param PageModel     $translation The translation.
     * @param array         $pages       List of page urls.
     * @param array         $processed   Cache of processed paged.
     * @param bool          $isSitemap   Sitemap.
     * @param int           $time        The time.
     *
     * @return array
     */
    private function processTranslation(
        CalendarModel $calendar,
        PageModel $translation,
        array $pages,
        array &$processed,
        bool $isSitemap,
        int $time
    ) {
        // Get the URL of the jumpTo page
        if (!isset($processed[$calendar->jumpTo][$translation->id])) {
            // The target page has not been published (see #5520)
            if (!$this->isPagePublished($translation, $time)) {
                return $pages;
            }

            if (!$this->shouldPageBeAddedToSitemap($translation, $isSitemap)) {
                return $pages;
            }

            // Generate the URL
            $processed[$calendar->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                $this->config->get('useAutoItem') ? '/%s' : '/events/%s'
            );
        }

        $strUrl = $processed[$calendar->jumpTo][$translation->id];

        // Get the items
        /** @var CalendarEventsModel|Repository $eventsRepository */
        $eventsRepository = $this->repositoryManager->getRepository(CalendarEventsModel::class);
        $objEvents        = $eventsRepository->findPublishedDefaultByPid($calendar->id);

        if ($objEvents !== null) {
            while ($objEvents->next()) {
                $pages[] = sprintf(
                    preg_replace('/%(?!s)/', '%%', $strUrl),
                    ($objEvents->alias ?: $objEvents->id)
                );
            }
        }

        return $pages;
    }
}
