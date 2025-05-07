<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Article;

use Contao\ArticleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

final class TranslatedArticleFinder
{
    public function __construct(private readonly RepositoryManager $repositoryManager)
    {
    }

    /**
     * Get the article modes.
     *
     * @param object|PageModel $currentPage The current page.
     * @param string|null      $filter      Optional filter by a mode.
     *
     * @return array<int,string>
     */
    public function getArticleModes(object $currentPage, string|null $filter = null): array
    {
        $modes = [];

        foreach (StringUtil::deserialize($currentPage->i18n_articles, true) as $config) {
            if (! $config['article']) {
                continue;
            }

            if ($filter !== null && $config['mode'] !== $filter) {
                continue;
            }

            $modes[(int) $config['article']] = $config['mode'];
        }

        return $modes;
    }

    /**
     * Get the override articles.
     *
     * List index is the id of the main article.
     *
     * @param object|PageModel $currentPage The current page.
     *
     * @return array|ArticleModel[]
     */
    public function getOverrides(object $currentPage): array
    {
        $repository = $this->repositoryManager->getRepository(ArticleModel::class);
        $collection = $repository->findBy(['.languageMain != \'0\'', '.pid=?'], [$currentPage->id]);

        if (! $collection) {
            return [];
        }

        $overrides = [];
        foreach ($collection as $articleModel) {
            $overrides[$articleModel->languageMain] = $articleModel;
        }

        return $overrides;
    }
}
