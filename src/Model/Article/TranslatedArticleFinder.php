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

namespace Netzmacht\Contao\I18n\Model\Article;

use Contao\ArticleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Netzmacht\Contao\Toolkit\Data\Model\Repository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

/**
 * Class I18nArticles
 *
 * @package Netzmacht\Contao\I18n\Util
 */
final class TranslatedArticleFinder
{
    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * TranslatedArticlesFinder constructor.
     *
     * @param RepositoryManager $repositoryManager Repository manager.
     */
    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * Get the article modes.
     *
     * @param PageModel|object $currentPage The current page.
     * @param string|null      $filter      Optional filter by a mode.
     *
     * @return array
     */
    public function getArticleModes($currentPage, ?string $filter = null): array
    {
        $modes = [];

        foreach (StringUtil::deserialize($currentPage->i18n_articles, true) as $config) {
            if (!$config['article']) {
                continue;
            }

            if ($filter && $config['mode'] !== $filter) {
                continue;
            }

            $modes[$config['article']] = $config['mode'];
        }

        return $modes;
    }

    /**
     * Get the override articles.
     *
     * List index is the id of the main article.
     *
     * @param PageModel|object $currentPage The current page.
     *
     * @return array|ArticleModel[]
     */
    public function getOverrides($currentPage): array
    {
        /** @var Repository|ArticleModel $repository */
        $repository = $this->repositoryManager->getRepository(ArticleModel::class);
        $collection = $repository->findBy(['.languageMain != \'\'', '.pid=?'], [$currentPage->id]);

        if (!$collection) {
            return [];
        }

        $overrides = [];
        foreach ($collection as $articleModel) {
            $overrides[$articleModel->languageMain] = $articleModel;
        }

        return $overrides;
    }
}
