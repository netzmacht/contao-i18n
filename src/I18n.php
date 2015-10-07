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

use Contao\PageModel;

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
     * I18n constructor.
     *
     * @param array $i18nPageTypes Set of supported i18n pages.
     */
    public function __construct(array $i18nPageTypes)
    {
        $this->i18nPageTypes = $i18nPageTypes;
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
     * @param PageModel|int $page The page as model or id.
     *
     * @return PageModel|null
     */
    public function getBasePage($page)
    {
        if (!$page instanceof PageModel) {
            $page = PageModel::findByPk($page);
        }

        if (!$page) {
            return null;
        }

        $pageId = $page->id;

        if (!$this->isI18nPage($page->type)) {
            return $page;
        }

        if (!array_key_exists($pageId, $this->basePages)) {
            $this->basePages[$pageId] = PageModel::findByPk($page->languageMain);
        }

        return $this->basePages[$pageId];
    }

    /**
     * Get the translated page for a given page.
     *
     * @param PageModel|int $page The page as model or id.
     *
     * @return PageModel|null
     */
    public function getTranslatedPage($page)
    {
        if (!$page instanceof PageModel) {
            $page = PageModel::findByPk($page);
        }

        if (!$page) {
            return null;
        }

        $language = $this->getCurrentLanguage();

        // Already got it.
        if ($language === $page->language) {
            $this->translatedPages[$language][$page->id] = $page;
        } else {
            $rootPage = $this->getRootPage($page);

            // Current page is not in the fallback language tree. Not able to find the translated page.
            if (!$rootPage->fallback) {
                $this->translatedPages[$language][$page->id] = null;
            }

            $translatedPages = PageModel::findBy(array('tl_page.languageMain=?'), array($page->id));

            $this->translatedPages[$language][$page->id] = null;

            if ($translatedPages) {
                foreach ($translatedPages as $translatedPage) {
                    $translatedPage->loadDetails();

                    if ($translatedPage->language === $language) {
                        $this->translatedPages[$language][$page->id] = $translatedPage;
                        return $translatedPage;
                    }
                }
            }
        }

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
            return PageModel::findByPk($page->cca_rr_root);
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
