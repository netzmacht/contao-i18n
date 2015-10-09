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

use Contao\Database;
use Contao\Model\Registry;
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
     * Database connection.
     *
     * @var Database
     */
    private $database;

    /**
     * Model registry.
     *
     * @var Registry
     */
    private $modelRegistry;

    /**
     * I18n constructor.
     *
     * @param array    $i18nPageTypes Set of supported i18n pages.
     * @param Database $database      Database connection.
     * @param Registry $modelRegistry Model registry.
     */
    public function __construct(array $i18nPageTypes, Database $database, Registry $modelRegistry)
    {
        $this->i18nPageTypes = $i18nPageTypes;
        $this->database      = $database;
        $this->modelRegistry = $modelRegistry;
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
            if (array_key_exists($page, $this->basePages)) {
                return $this->basePages[$page];
            }

            $page = PageModel::findByPk($page);
        }

        if (!$page || !$this->isI18nPage($page->type)) {
            return $page;
        }

        if (!array_key_exists($page->id, $this->basePages)) {
            $this->basePages[$page->id] = PageModel::findByPk($page->languageMain);
        }

        return $this->basePages[$page->id];
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
        $language = $this->getCurrentLanguage();

        if (!$page instanceof PageModel) {
            if (isset($this->translatedPages[$language][$page])) {
                return $this->translatedPages[$language][$page];
            }

            $page = PageModel::findByPk($page);
        }

        $this->translatedPages[$language][$page->id] = $page;
        if (!$page || $page->language === $language) {
            return $page;
        }

        $rootPage = $this->getRootPage($page);
        // Current page is not in the fallback language tree. Not able to find the translated page.
        if (!$rootPage->fallback) {
            return null;
        }

        $this->translatedPages[$language][$page->id] = $this->findTranslatedPage($page, $language);

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

    /**
     * Find the translated page.
     *
     * @param PageModel $page     Related main page.
     * @param string    $language The requested language.
     *
     * @return PageModel|null
     */
    private function findTranslatedPage($page, $language)
    {
        $query = <<<SQL
SELECT p.* FROM tl_page p
JOIN tl_page r ON r.id = p.cca_rr_root AND r.language = ?
WHERE p.languageMain = ?
SQL;

        $result = $this->database->prepare($query)->limit(1)->execute($language, $page->id);
        if ($result->numRows < 1) {
            return null;
        }

        $page = $this->modelRegistry->fetch('tl_page', $result->id);
        if ($page) {
            return $page;
        }

        return new PageModel($result);
    }
}
