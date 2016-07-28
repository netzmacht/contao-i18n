<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\I18n;

use Netzmacht\Contao\I18n\Model\Repository\PageRepository;
use PageModel;

/**
 * Class I18n.
 *
 * @package Netzmacht\Contao\I18n
 */
class I18n
{
    /**
     * Set of supported i18n pages.
     *
     * @var array
     */
    private $i18nPageTypes;

    /**
     * I18n page cache for base pages.
     *
     * For each language page the base page get cached at runtime.
     *
     * @var PageModel[]|null[]
     */
    private $basePages = [];

    /**
     * I18n page cache for translated pages.
     *
     * Each translated page of a page get cached at runtime.
     *
     * @var PageModel[][]|null[][]
     */
    private $translatedPages = [];

    /**
     * Page translations.
     *
     * @var PageModel[][]
     */
    private $translations = [];

    /**
     * Page repository.
     *
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * I18n constructor.
     *
     * @param array          $i18nPageTypes  Set of supported i18n pages.
     * @param PageRepository $pageRepository Page repository.
     */
    public function __construct(array $i18nPageTypes, PageRepository $pageRepository)
    {
        $this->i18nPageTypes  = $i18nPageTypes;
        $this->pageRepository = $pageRepository;
    }

    /**
     * Check if the given page type is an i18n page type.
     *
     * @param string $pageType The page type.
     *
     * @return bool
     */
    public function isI18nPage($pageType)
    {
        return in_array($pageType, $this->i18nPageTypes);
    }

    /**
     * Get the base page for a given page id. If the page is not an 18n page the page for the page id is returned.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     *
     * @return PageModel|null
     */
    public function getBasePage($page)
    {
        if (!$page instanceof PageModel) {
            if (array_key_exists($page, $this->basePages)) {
                return $this->basePages[$page];
            }

            $page = $this->pageRepository->find($page);
        }

        if (!$page || !$this->isI18nPage($page->type)) {
            return $page;
        }

        if (!array_key_exists($page->id, $this->basePages)) {
            $this->basePages[$page->id] = $this->pageRepository->find($page->languageMain);
        }

        return $this->basePages[$page->id];
    }

    /**
     * Get the main page of a connected language page.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     *
     * @return PageModel|null
     */
    public function getMainPage($page)
    {
        if (!$page instanceof PageModel) {
            $page = $this->pageRepository->find($page);
        }

        if (!$page) {
            return null;
        }

        // Current page is already the main page.
        if ($page->languageMain == 0) {
            return $page;
        }

        return $this->pageRepository->find($page->languageMain);
    }

    /**
     * Get all translations of a page.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     *
     * @return PageModel[]
     */
    public function getPageTranslations($page)
    {
        $pages    = [];
        $mainPage = $this->getMainPage($page);

        if (!$mainPage) {
            return $pages;
        }

        if (array_key_exists($mainPage->id, $this->translations)) {
            return $this->translations[$mainPage->id];
        }

        $language         = $this->getPageLanguage($mainPage);
        $pages[$language] = $mainPage;

        foreach ($this->pageRepository->findBy(['tl_page.languageMain = ?'], [$mainPage->id]) as $page) {
            $language         = $this->getPageLanguage($page);
            $pages[$language] = $page;
        }

        $this->translations[$mainPage->id] = $pages;

        return $pages;
    }

    /**
     * Get the language of a page.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     *
     * @return string|null
     */
    public function getPageLanguage($page)
    {
        if (!$page instanceof PageModel) {
            $page = $this->pageRepository->find($page);
        }

        if (!$page) {
            return null;
        }

        // Page with loaded details.
        if ($page->language) {
            return $page->language;
        }

        $root = $this->getRootPage($page);
        if ($root) {
            return $root->language;
        }

        return null;
    }

    /**
     * Get the translated page for a given page.
     *
     * @param PageModel|int|string $page     The page as model or id/alias.
     * @param string               $language If set a specific language is loaded. Otherwise the current language.
     *
     * @return null|PageModel
     */
    public function getTranslatedPage($page, $language = null)
    {
        $language = $language ?: $this->getCurrentLanguage();

        if (!$page instanceof PageModel) {
            if (isset($this->translatedPages[$language][$page])) {
                return $this->translatedPages[$language][$page];
            }

            $page = $this->pageRepository->find($page);
        }

        $this->translatedPages[$language][$page->id] = $page;

        // No page found, return.
        if (!$page) {
            return null;
        }

        // Load root page to get language.
        $rootPage = $this->getRootPage($page);

        if ($rootPage->language === $language) {
            return $page;
        }

        // Current page is not in the fallback language tree. Not able to find the translated page.
        if (!$rootPage->fallback) {
            return null;
        }

        $this->translatedPages[$language][$page->id] = $this->pageRepository->findTranslatedPage($page->id, $language);

        return $this->translatedPages[$language][$page->id];
    }

    /**
     * Get the root page for a page.
     *
     * @param PageModel $page The page model.
     *
     * @return \Model|null
     */
    private function getRootPage(PageModel $page)
    {
        if ($page->cca_rr_root > 0) {
            return $this->pageRepository->find($page->cca_rr_root);
        }

        return null;
    }

    /**
     * Get the current language.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getCurrentLanguage()
    {
        return $GLOBALS['TL_LANGUAGE'];
    }
}
