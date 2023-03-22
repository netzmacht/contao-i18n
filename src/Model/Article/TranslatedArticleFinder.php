<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Article;

use Contao\ArticleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

final class TranslatedArticleFinder
{
    private RepositoryManager $repositoryManager;

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
     * @return array<int,string>
     */
    public function getArticleModes($currentPage, ?string $filter = null): array
    {
        $modes = [];

        foreach (StringUtil::deserialize($currentPage->i18n_articles, true) as $config) {
            if (! $config['article']) {
                continue;
            }

            if ($filter && $config['mode'] !== $filter) {
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
     * @param PageModel|object $currentPage The current page.
     *
     * @return array|ArticleModel[]
     */
    public function getOverrides($currentPage): array
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
