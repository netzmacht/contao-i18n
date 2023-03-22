<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\PageType;

use Contao\ArticleModel;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\System;

use function assert;
use function class_exists;
use function explode;
use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function sprintf;
use function strlen;

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
        if (! is_object($moduleId) && ! strlen((string) $moduleId)) {
            return '';
        }

        $currentPage = static::getContainer()->get('netzmacht.contao_i18n.page_provider')->getPage();
        $i18n        = static::getContainer()->get('netzmacht.contao_i18n.page_repository');

        if (! $i18n->isI18nPage($currentPage->type)) {
            return parent::getFrontendModule($moduleId, $column);
        }

        $basePage = $i18n->getBasePage($currentPage);
        if (! $basePage) {
            return '';
        }

        if ((int) $moduleId === 0) {
            // Articles
            return self::getArticles($currentPage, $basePage, $column);
        }

        return self::generateFrontendModule((int) $moduleId, $column);
    }

    /**
     * Get the articles of a page.
     *
     * @param PageModel $currentPage I18n page.
     * @param PageModel $basePage    Base page.
     * @param string    $column      Article column.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private static function getArticles($currentPage, $basePage, string $column = 'main'): string
    {
        // Show a particular article only
        if ($basePage->type === 'regular' && Input::get('articles')) {
            [$section, $article] = explode(':', Input::get('articles'));

            if ($article === null) {
                $article = $section;
                $section = 'main';
            }

            if ($section === $column) {
                return self::generateSectionArticle($basePage, $article);
            }
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getArticles']) && is_array($GLOBALS['TL_HOOKS']['getArticles'])) {
            foreach ($GLOBALS['TL_HOOKS']['getArticles'] as $callback) {
                $return = static::importStatic($callback[0])->{$callback[1]}($basePage->id, $column);

                if (is_string($return)) {
                    return $return;
                }
            }
        }

        return static::generateArticleList($currentPage, $basePage, $column);
    }

    /**
     * Generate the frontend module.
     *
     * @param int    $moduleId Module id.
     * @param string $column   Layout column.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private static function generateFrontendModule(int $moduleId, string $column): string
    {
        $moduleModel = static::getModuleModel($moduleId);

        // Check the visibility (see #6311)
        if (! $moduleModel || ! static::isVisibleElement($moduleModel)) {
            return '';
        }

        $moduleClass = Module::findClass($moduleModel->type);

        // Return if the class does not exist
        if (! class_exists($moduleClass)) {
            static::log(
                sprintf('Module class "%s" (module "%s") does not exist', $moduleClass, $moduleModel->type),
                __METHOD__,
                TL_ERROR
            );

            return '';
        }

        $moduleModel->typePrefix = 'mod_';

        $module = new $moduleClass($moduleModel, $column);
        assert($module instanceof Module);
        $buffer = $module->generate();

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getFrontendModule']) && is_array($GLOBALS['TL_HOOKS']['getFrontendModule'])) {
            foreach ($GLOBALS['TL_HOOKS']['getFrontendModule'] as $callback) {
                $buffer = static::importStatic($callback[0])->{$callback[1]}($moduleModel, $buffer, $module);
            }
        }

        // Disable indexing if protected
        if ($module->protected && ! preg_match('/^\s*<!-- indexer::stop/', $buffer)) {
            $buffer = "\n<!-- indexer::stop -->" . $buffer . "<!-- indexer::continue -->\n";
        }

        return $buffer;
    }

    /**
     * Get a module model.
     *
     * @param int|ModuleModel $moduleId Module model or id.
     */
    private static function getModuleModel($moduleId): ?ModuleModel
    {
        // Other modules
        if (is_object($moduleId)) {
            return $moduleId;
        }

        return ModuleModel::findByPk($moduleId);
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
        if (! static::isVisibleElement($articleModel)) {
            throw new AccessDeniedException('Access denied: ' . Environment::get('uri'));
        }

        // Add the "first" and "last" classes (see #2583)
        $articleModel->classes = ['first', 'last'];

        return static::getArticle($articleModel);
    }

    /**
     * Generate the article list.
     *
     * @param PageModel $currentPage I18n page model.
     * @param PageModel $basePage    Page model.
     * @param string    $column      Section column.
     */
    private static function generateArticleList(PageModel $currentPage, PageModel $basePage, string $column): string
    {
        // Show all articles (no else block here, see #4740)
        $articles = ArticleModel::findPublishedByPidAndColumn($basePage->id, $column);

        if ($articles === null) {
            return '';
        }

        $finder    = System::getContainer()->get('netzmacht.contao_i18n.translated_article_finder');
        $modes     = $finder->getArticleModes($currentPage);
        $overrides = $finder->getOverrides($currentPage);
        $return    = '';
        $count     = 0;
        $multiMode = $articles->count() > 1;
        $last      = $articles->count() - 1;

        foreach ($articles as $articleModel) {
            // Article should be overridden. So replace it.
            if (isset($overrides[$articleModel->id])) {
                $articleModel = $overrides[$articleModel->id];
            } elseif (isset($modes[$articleModel->id])) {
                // Article is marked as exclude or as overriden.
                // For the last case - the referenced article does not exist.
                continue;
            }

            // Add the "first" and "last" classes (see #2583)
            if ($count === 0 || $count === $last) {
                $arrCss = [];

                if ($count === 0) {
                    $arrCss[] = 'first';
                }

                if ($count === $last) {
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
