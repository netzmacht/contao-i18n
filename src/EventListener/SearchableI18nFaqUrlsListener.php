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

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Database;
use Contao\Date;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\Toolkit\Data\Model\Repository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

/**
 * Class SearchableI18nFaqUrlsListener
 *
 * @package Netzmacht\Contao\I18n\EventListener
 */
class SearchableI18nFaqUrlsListener extends AbstractContentSearchableUrlsListener
{
    /**
     * Model repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * I18n page repository.
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
     * SearchableI18nNewsUrlsListener constructor.
     *
     * @param RepositoryManager  $repositoryManager  Model repository manager.
     * @param I18nPageRepository $i18nPageRepository I18n page repository.
     * @param Database           $database           Legacy contao database connection.
     * @param Config|Adapter     $config             Contao config adapter.
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
        $pages     = [];
        $root      = $this->getPageChildRecords($pid);
        $processed = [];
        $time      = Date::floorToMinute();

        // Get all categories
        /** @var Repository|FaqCategoryModel $categoryRepository */
        $categoryRepository = $this->repositoryManager->getRepository(FaqCategoryModel::class);
        $collection         = $categoryRepository->findAll();

        // Walk through each category
        if ($collection === null) {
            return $pages;
        }

        while ($collection->next()) {
            // Skip FAQs without target page
            if (!$collection->jumpTo) {
                continue;
            }

            $translations = $this->i18nPageRepository->getPageTranslations($collection->jumpTo);

            foreach ($translations as $translation) {
                // Skip FAQs outside the root nodes
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
     * @param FaqCategoryModel $category    The faq category.
     * @param PageModel        $translation The translation.
     * @param array            $pages       List of page urls.
     * @param array            $processed   Cache of processed paged.
     * @param bool             $isSitemap   Sitemap.
     * @param int              $time        The time.
     *
     * @return array
     */
    private function processTranslation(
        FaqCategoryModel $category,
        PageModel $translation,
        array $pages,
        array &$processed,
        bool $isSitemap,
        int $time
    ): array {
        // Get the URL of the jumpTo page
        if (!isset($processed[$category->jumpTo][$translation->id])) {
            // The target page has not been published (see #5520)
            if (!$this->isPagePublished($translation, $time)) {
                return $pages;
            }

            if (!$this->shouldPageBeAddedToSitemap($translation, $isSitemap)) {
                return $pages;
            }

            // Generate the URL
            $processed[$category->jumpTo][$translation->id] = $translation->getAbsoluteUrl(
                $this->config->get('useAutoItem') ? '/%s' : '/items/%s'
            );
        }

        // Get the items
        /** @var FaqModel|Repository $faqRepository */
        $faqRepository = $this->repositoryManager->getRepository(FaqModel::class);
        $items         = $faqRepository->findPublishedByPid($category->id);
        $url           = $processed[$category->jumpTo][$translation->id];

        if ($items !== null) {
            while ($items->next()) {
                $pages[] = sprintf(
                    preg_replace('/%(?!s)/', '%%', $url),
                    ($items->alias ?: $items->id)
                );
            }
        }

        return $pages;
    }
}
