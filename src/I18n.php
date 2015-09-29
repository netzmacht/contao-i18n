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
     * I18n page cache.
     *
     * @var PageModel[]|null[]
     */
    private $pages = [];

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

        if (!array_key_exists($pageId, $this->pages)) {
            $this->pages[$pageId] = PageModel::findByPk($page->languageMain);
        }

        return $this->pages[$pageId];
    }
}
