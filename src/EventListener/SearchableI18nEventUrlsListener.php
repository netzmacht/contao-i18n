<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\Repository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

/**
 * Class SearchableI18nEventUrlsListener
 */
final class SearchableI18nEventUrlsListener extends AbstractSearchableUrlsListener
{
    /**
     * I18nPageRepository page repository.
     *
     * @var I18nPageRepository
     */
    private $i18nPageRepository;

    /**
     * Legacy contao database connection.
     *
     * @var Database
     */
    private $database;

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
        $this->repositoryManager  = $repositoryManager;
        $this->i18nPageRepository = $i18nPageRepository;
        $this->database           = $database;
        $this->config             = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectPages($pid = 0, string $domain = '', bool $isSitemap = false): array
    {
        $root      = [];
        $processed = [];
        $time      = Date::floorToMinute();
        $pages     = [];

        if ($pid > 0) {
            $root = $this->database->getChildRecords($pid, 'tl_page');
        }

        // Get all calendars
        /** @var CalendarModel|Repository $calendarRepository */
        $calendarRepository = $this->repositoryManager->getRepository(CalendarModel::class);

        /** @var CalendarEventsModel|Repository $eventsRepository */
        $eventsRepository   = $this->repositoryManager->getRepository(CalendarEventsModel::class);
        $collection         = $calendarRepository->findByProtected('');

        // Walk through each calendar
        if ($collection !== null) {
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

                    // Get the URL of the jumpTo page
                    if (!isset($processed[$collection->jumpTo][$translation->id])) {
                        // The target page has not been published (see #5520)
                        if (!$translation->published
                            || ($translation->start != '' && $translation->start > $time)
                            || ($translation->stop != '' && $translation->stop <= ($time + 60))
                        ) {
                            continue;
                        }

                        if ($isSitemap) {
                            // The target page is protected (see #8416)
                            if ($translation->protected) {
                                continue;
                            }

                            // The target page is exempt from the sitemap (see #6418)
                            if ($translation->sitemap == 'map_never') {
                                continue;
                            }
                        }

                        // Generate the URL
                        $processed[$collection->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                            $this->config->get('useAutoItem') ? '/%s' : '/events/%s'
                        );
                    }

                    $strUrl = $processed[$collection->jumpTo][$translation->id];

                    // Get the items
                    $objEvents = $eventsRepository->findPublishedDefaultByPid($collection->id);

                    if ($objEvents !== null) {
                        while ($objEvents->next()) {
                            $pages[] = sprintf(
                                preg_replace('/%(?!s)/', '%%', $strUrl),
                                ($objEvents->alias ?: $objEvents->id)
                            );
                        }
                    }
                }
            }
        }

        return $pages;
    }
}
