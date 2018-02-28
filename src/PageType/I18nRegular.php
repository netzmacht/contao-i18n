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

namespace Netzmacht\Contao\I18n\PageType;

use Contao\ArticleModel;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\PageRegular;

/**
 * Regular i18n page load the content of the base page.
 */
class I18nRegular extends PageRegular
{
    /**
     * {@inheritDoc}
     */
    public static function getFrontendModule($moduleId, $column = 'main')
    {
        if (!is_object($moduleId) && !strlen($moduleId)) {
            return '';
        }

        $currentPage = static::getContainer()->get('netzmacht.contao_i18n.page_provider')->getPage();
        $i18n        = static::getContainer()->get('netzmacht.contao_i18n.page_repository');

        if (!$i18n->isI18nPage($currentPage->type)) {
            return parent::getFrontendModule($moduleId, $column);
        }

        $basePage = $i18n->getBasePage($currentPage);
        if (!$basePage) {
            return '';
        }

        if ($moduleId == 0) {
            // Articles
            return self::getArticles($basePage, $column);
        } else {
            return self::generateFrontendModule($moduleId, $column);
        }
    }

    /**
     * Get the articles of a page.
     *
     * @param PageModel $basePage Base page.
     * @param string    $column   Article column.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private static function getArticles($basePage, $column = 'main')
    {
        // Show a particular article only
        if ($basePage->type == 'regular' && Input::get('articles')) {
            list($section, $article) = explode(':', Input::get('articles'));

            if ($article === null) {
                $article = $section;
                $section = 'main';
            }

            if ($section == $column) {
                return self::generateSectionArticle($basePage, $article);
            }
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getArticles']) && \is_array($GLOBALS['TL_HOOKS']['getArticles'])) {
            foreach ($GLOBALS['TL_HOOKS']['getArticles'] as $callback) {
                $return = static::importStatic($callback[0])->{$callback[1]}($basePage->id, $column);

                if (\is_string($return)) {
                    return $return;
                }
            }
        }

        return static::generateArticleList($basePage, $column);
    }

    /**
     * Generate the frontend module.
     *
     * @param int    $moduleId Module id.
     * @param string $column   Layout column.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private static function generateFrontendModule($moduleId, $column)
    {
        $moduleModel = static::getModuleModel($moduleId);

        // Check the visibility (see #6311)
        if (!$moduleModel || !static::isVisibleElement($moduleModel)) {
            return '';
        }

        $moduleClass = Module::findClass($moduleModel->type);

        // Return if the class does not exist
        if (!class_exists($moduleClass)) {
            static::log(
                sprintf('Module class "%s" (module "%s") does not exist', $moduleClass, $moduleModel->type),
                __METHOD__,
                TL_ERROR
            );
            return '';
        }

        $moduleModel->typePrefix = 'mod_';
        /** @var Module $module */
        $module = new $moduleClass($moduleModel, $column);
        $buffer = $module->generate();

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getFrontendModule']) && is_array($GLOBALS['TL_HOOKS']['getFrontendModule'])) {
            foreach ($GLOBALS['TL_HOOKS']['getFrontendModule'] as $callback) {
                $buffer = static::importStatic($callback[0])->{$callback[1]}($moduleModel, $buffer, $module);
            }
        }

        // Disable indexing if protected
        if ($module->protected && !preg_match('/^\s*<!-- indexer::stop/', $buffer)) {
            $buffer = "\n<!-- indexer::stop -->". $buffer ."<!-- indexer::continue -->\n";
        }

        return $buffer;
    }

    /**
     * Get a model model.
     *
     * @param int|ModuleModel $moduleId Module model or id.
     *
     * @return ModuleModel|null
     */
    private static function getModuleModel($moduleId)
    {
        // Other modules
        if (is_object($moduleId)) {
            return $moduleId;
        }

        return \ModuleModel::findByPk($moduleId);
    }

    /**
     * Generate the articles in a section when get param articles is given.
     *
     * @param PageModel  $basePage Base page.
     * @param int|string $article  Article alias or id.
     *
     * @return bool|string
     *
     * @throws PageNotFoundException If page does not exist.
     * @throws AccessDeniedException If article is not visible.
     */
    private static function generateSectionArticle(PageModel $basePage, $article)
    {
        $articleModel = ArticleModel::findByIdOrAliasAndPid($article, $basePage->id);

        // Send a 404 header if the article does not exist
        if ($articleModel === null) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Send a 403 header if the article cannot be accessed
        if (!static::isVisibleElement($articleModel)) {
            throw new AccessDeniedException('Access denied: ' . Environment::get('uri'));
        }

        // Add the "first" and "last" classes (see #2583)
        $articleModel->classes = ['first', 'last'];

        return static::getArticle($articleModel);
    }

    /**
     * Generate the article list.
     *
     * @param PageModel $basePage Page model.
     * @param string    $column   Section column.
     *
     * @return string
     */
    private static function generateArticleList(PageModel $basePage, $column)
    {
        // Show all articles (no else block here, see #4740)
        $articles = ArticleModel::findPublishedByPidAndColumn($basePage->id, $column);

        if ($articles === null) {
            return '';
        }

        $return    = '';
        $count     = 0;
        $multiMode = ($articles->count() > 1);
        $last      = ($articles->count() - 1);

        while ($articles->next()) {
            $articleModel = $articles->current();

            // Add the "first" and "last" classes (see #2583)
            if ($count == 0 || $count == $last) {
                $arrCss = array();

                if ($count == 0) {
                    $arrCss[] = 'first';
                }

                if ($count == $last) {
                    $arrCss[] = 'last';
                }

                $articleModel->classes = $arrCss;
            }

            $return .= static::getArticle($articleModel, $multiMode, false, $column);
            ++$count;
        }

        return $return;
    }
}
