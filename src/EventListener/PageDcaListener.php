<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\ArticleModel;
use Contao\Backend;
use Contao\BackendUser;
use Contao\ContentModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Netzmacht\Contao\I18n\Cleanup\I18nPageArticleCleaner;
use Netzmacht\Contao\I18n\Model\Article\TranslatedArticleFinder;
use Netzmacht\Contao\Toolkit\Callback\Invoker;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;

use function array_filter;
use function array_keys;
use function in_array;
use function preg_replace;
use function sprintf;
use function time;

final class PageDcaListener extends AbstractListener
{
    /**
     * Data container name.
     *
     * @var string
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $name = 'tl_page';

    private RepositoryManager $repositoryManager;

    private BackendUser $user;

    private TranslatedArticleFinder $articleFinder;

    /**
     * If true all unrelated articles get removed.
     */
    private bool $articleCleanup;

    private I18nPageArticleCleaner $articleCleaner;

    private Invoker $callbackInvoker;

    /**
     * @param Manager                 $dcaManager        Data container definition manager.
     * @param RepositoryManager       $repositoryManager Repository manager.
     * @param BackendUser             $user              Backend user.
     * @param TranslatedArticleFinder $articleFinder     Article finder.
     * @param I18nPageArticleCleaner  $articleCleaner    Article cleaner.
     * @param Invoker                 $callbackInvoker   Callback invoker.
     * @param bool                    $articleCleanup    If true all unrelated articles get removed.
     */
    public function __construct(
        Manager $dcaManager,
        RepositoryManager $repositoryManager,
        BackendUser $user,
        TranslatedArticleFinder $articleFinder,
        I18nPageArticleCleaner $articleCleaner,
        Invoker $callbackInvoker,
        bool $articleCleanup
    ) {
        parent::__construct($dcaManager);

        $this->repositoryManager = $repositoryManager;
        $this->user              = $user;
        $this->articleFinder     = $articleFinder;
        $this->articleCleaner    = $articleCleaner;
        $this->callbackInvoker   = $callbackInvoker;
        $this->articleCleanup    = $articleCleanup;
    }

    /**
     * Initialize the palette.
     *
     * @param DataContainer $dataContainer Data container driver.
     */
    public function initializePalette(DataContainer $dataContainer): void
    {
        $definition = $this->getDefinition();
        $definition->set(['palettes', 'i18n_regular'], $definition->get(['palettes', 'regular']));

        PaletteManipulator::create()
            ->addLegend('articles_legend', ['meta_legend', 'language_legend', 'title_legend'])
            ->addField('i18n_articles', 'articles_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('i18n_regular', static::getName());

        if (Input::get('act') !== 'edit') {
            return;
        }

        $repository = $this->repositoryManager->getRepository(PageModel::class);
        $page       = $repository->find((int) $dataContainer->id);

        if (! $page || $page->type === 'root' || $page->type === 'i18n_regular' || $page->languageMain > 0) {
            return;
        }

        PaletteManipulator::create()
            ->addField('i18n_disable', 'expert_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('regular', static::getName());
    }

    /**
     * Initialize the page type options.
     */
    public function initializePageTypeOptionsCallback(): void
    {
        $definition = $this->getDefinition();
        $callback   = $definition->get(['fields', 'type', 'options_callback']);
        $definition->set(['fields', 'type', 'options_callback_original'], $callback);
        $definition->set(
            ['fields', 'type', 'options_callback'],
            [
                'netzmacht.contao_i18n.listeners.dca.page',
                'pageTypeOptions',
            ]
        );
    }

    /**
     * Get the page type options.
     *
     * @param DataContainer|null $dataContainer Data container driver.
     *
     * @return list<string>
     */
    public function pageTypeOptions(?DataContainer $dataContainer = null): array
    {
        $callback = $this->getDefinition()->get(['fields', 'type', 'options_callback_original']);
        if (! $callback || ! $dataContainer) {
            return [];
        }

        $options    = $this->callbackInvoker->invoke($callback, [$dataContainer]);
        $repository = $this->repositoryManager->getRepository(PageModel::class);

        $page = $repository->find((int) $dataContainer->id);
        if (! $page) {
            return $options;
        }

        $rootPage = $repository->find((int) $page->hofff_root_page_id);
        if (! $rootPage) {
            return $options;
        }

        if ($rootPage->fallback === '1' && $rootPage->languageRoot === '0') {
            $options = array_filter($options, static fn ($value): bool => $value !== 'i18n_regular');
        }

        return $options;
    }

    /**
     * Get all articles of the base page as options array.
     *
     * @return array<string|int,string>
     */
    public function getBasePageArticlesOptions(): array
    {
        if (Input::get('act') !== 'edit') {
            return [];
        }

        $pageRepository = $this->repositoryManager->getRepository(PageModel::class);
        $pageModel      = $pageRepository->find((int) Input::get('id'));
        if (! $pageModel) {
            return [];
        }

        $repository = $this->repositoryManager->getRepository(ArticleModel::class);
        $collection = $repository->findByPid($pageModel->languageMain);
        if (! $collection) {
            return [];
        }

        $options = [];
        foreach ($collection as $article) {
            $options[$article->id] = sprintf(
                '%s (ID %s) [%s]',
                $article->title,
                $article->id,
                $article->inColumn
            );
        }

        return $options;
    }

    /**
     * Create all references i18n articles.
     *
     * @param DataContainer|null $dataContainer The data container.
     *
     * @throws InvalidArgumentException When an invalid query was built.
     */
    public function createI18nArticles(?DataContainer $dataContainer = null): void
    {
        if (
            ! $dataContainer
            || ! $dataContainer->activeRecord
            || $dataContainer->activeRecord->type !== 'i18n_regular'
        ) {
            return;
        }

        // Reload the page. Active record stores the version before the changes are applied.
        $page      = $this->repositoryManager->getRepository(PageModel::class)->find((int) $dataContainer->id);
        $modes     = $this->articleFinder->getArticleModes($page, 'override');
        $overrides = $this->articleFinder->getOverrides($page);
        $deletes   = $overrides;

        foreach (array_keys($modes) as $articleId) {
            unset($deletes[$articleId]);

            if (isset($overrides[$articleId])) {
                continue;
            }

            $this->copyArticle($dataContainer->id, $articleId);
        }

        // Remove connection between the languages. Do not delete the article.
        $articleRepository = $this->repositoryManager->getRepository(ArticleModel::class);

        foreach ($deletes as $article) {
            if ($this->articleCleanup) {
                $this->articleCleaner->deleteArticle($article, $dataContainer);

                continue;
            }

            $article->languageMain = 0;
            $article->tstamp       = time();

            $articleRepository->save($article);
        }

        // Delete all unrelated articles.
        if (! $this->articleCleanup) {
            return;
        }

        $this->articleCleaner->cleanupUnrelatedArticles($dataContainer);
    }

    /**
     * Load the i18n articles value.
     *
     * It combines all hard defined definitions with the latest changes.
     *
     * @param mixed         $raw          Raw value.
     * @param DataContainer $datContainer Data container driver.
     *
     * @return list<array<string,mixed>>
     */
    public function loadI18nArticles($raw, DataContainer $datContainer): array
    {
        if (Input::post('FORM_SUBMIT') === PageModel::getTable()) {
            return $raw;
        }

        $configured = StringUtil::deserialize($raw, true);
        $values     = [];
        $articles   = [];
        $collection = $this->repositoryManager
            ->getRepository(ArticleModel::class)
            ->findBy(['.pid=?', 'languageMain != 0'], [$datContainer->id]);

        if ($collection) {
            foreach ($collection as $article) {
                $articles[$article->languageMain] = $article;
            }
        }

        foreach ($configured as $config) {
            if ($config['article'] === '') {
                continue;
            }

            if ($config['mode'] === 'exclude') {
                $values[] = $config;
            }

            if (! isset($articles[$config['article']])) {
                continue;
            }

            $values[] = $config;
            unset($articles[$config['article']]);
        }

        foreach (array_keys($articles) as $articleId) {
            $values[] = [
                'article' => $articleId,
                'mode'    => 'override',
            ];
        }

        return $values;
    }

    /**
     * Generate an "edit articles" button and return it as string.
     *
     * @param array<string,mixed> $row        Current data row.
     * @param string              $href       The pre generated link.
     * @param string              $label      The label.
     * @param string              $title      The title attribute.
     * @param string              $icon       The icon path.
     * @param string              $attributes Html attributes.
     */
    public function editArticles(
        array $row,
        string $href,
        string $label,
        string $title,
        string $icon,
        string $attributes
    ): string {
        if (! $this->user->hasAccess('article', 'modules')) {
            return '';
        }

        if (! in_array($row['type'], ['regular', 'error_403', 'error_404', 'i18n_regular'])) {
            return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon));
        }

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;pn=' . $row['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }

    /**
     * Copy an article as translated version.
     *
     * @param int|string $pageId    Page id of the target page.
     * @param int|string $articleId Article id of the source article.
     */
    private function copyArticle($pageId, $articleId): void
    {
        $copy         = new ArticleModel();
        $articleModel = $this->repositoryManager->getRepository(ArticleModel::class)->find((int) $articleId);

        if ($articleModel) {
            $data = $articleModel->row();
            unset($data[ArticleModel::getPk()]);
            $copy->setRow($data);
        }

        $copy->pid          = $pageId;
        $copy->languageMain = $articleId;
        $copy->tstamp       = time();
        $this->repositoryManager->getRepository(ArticleModel::class)->save($copy);

        $contentElements = $this->repositoryManager->getRepository(ContentModel::class)->findBy(
            ['.pid=?', '( .ptable=? OR .ptable = \'\')'],
            [$articleId, ArticleModel::getTable()]
        );

        if (! $contentElements) {
            return;
        }

        foreach ($contentElements as $contentModel) {
            $contentCopy = new ContentModel();
            $data        = $contentModel->row();
            unset($data[ContentModel::getPk()]);

            $contentCopy->setRow($data);
            $contentCopy->pid    = $copy->id;
            $contentCopy->ptable = ArticleModel::getTable();
            $contentCopy->tstamp = time();

            $this->repositoryManager->getRepository(ContentModel::class)->save($contentCopy);
        }
    }
}
