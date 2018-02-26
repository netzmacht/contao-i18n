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

namespace Netzmacht\Contao\I18n\Merger2;

use Netzmacht\Contao\I18n\I18nTrait;

/**
 * I18n functions for merger2.
 *
 * @package Netzmacht\Contao\I18n\Merger2
 */
class I18nFunctions
{
    use I18nTrait;

    /**
     * Test the translated page id or alias.
     *
     * Function: i18nPage(..)
     *
     * @param mixed $pageIdOrAlias Page id or alias.
     *
     * @return bool
     */
    public static function i18nPage($pageIdOrAlias)
    {
        $currentPage = static::getCurrentPage();
        $page        = static::getTranslatedPage($pageIdOrAlias);

        return $currentPage === $page;
    }

    /**
     * Test the translated root page id or alias.
     *
     * Function: i18nRoot(..)
     *
     * @param mixed $pageIdOrAlias Page id or alias.
     *
     * @return bool
     */
    public static function i18nRoot($pageIdOrAlias)
    {
        $currentPage = static::getCurrentPage();
        $page        = static::getTranslatedPage($pageIdOrAlias);

        return $currentPage->hofff_root_page_id === $page->hofff_root_page_id;
    }

    /**
     * Test if page id or alias is in path.
     *
     * Function: i18nPageInPath(..)
     *
     * @param mixed $pageIdOrAlias Page id or alias.
     *
     * @return bool
     */
    public static function i18nPageInPath($pageIdOrAlias)
    {
        $page   = static::getCurrentPage();
        $pageId = static::getTranslatedPage($pageIdOrAlias)->id;

        while (true) {
            if ($pageId == $page->id) {
                return true;
            }

            if ($page->pid < 1) {
                return false;
            }

            $page = \PageModel::findByPk($page->pid);
        }

        return false;
    }

    /**
     * Get the current page.
     *
     * @return null|\PageModel
     */
    private static function getCurrentPage()
    {
        return static::getServiceContainer()->getPageProvider()->getPage();
    }

    /**
     * Get translated page.
     *
     * @param mixed $pageIdOrAlias Page id or alias.
     *
     * @return null|\PageModel
     */
    private static function getTranslatedPage($pageIdOrAlias)
    {
        return static::getI18n()->getTranslatedPage($pageIdOrAlias);
    }
}
