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
use Contao\Database;
use Contao\Date;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;

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
     * SearchableI18nEventUrlsListener constructor.
     *
     * @param I18nPageRepository $i18nPageRepository I18n page repository.
     */
    public function __construct(I18nPageRepository $i18nPageRepository)
    {
        $this->i18nPageRepository = $i18nPageRepository;
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
            $root = Database::getInstance()->getChildRecords($pid, 'tl_page');
        }

        // Get all calendars
        $collection = CalendarModel::findByProtected('');

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
                    if (!empty($root) && !\in_array($translation->id, $root)) {
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
                            Config::get('useAutoItem') ? '/%s' : '/events/%s'
                        );
                    }

                    $strUrl = $processed[$collection->jumpTo][$translation->id];

                    // Get the items
                    $objEvents = CalendarEventsModel::findPublishedDefaultByPid($collection->id);

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
