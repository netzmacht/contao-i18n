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
use Netzmacht\Contao\Toolkit\Data\Model\Repository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;

/**
 * Class PageDcaListener
 */
final class PageDcaListener extends AbstractListener
{
    /**
     * Data container name.
     *
     * @var string
     */
    protected static $name = 'tl_page';

    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * Backend user.
     *
     * @var BackendUser
     */
    private $user;

    /**
     * Translated article finder.
     *
     * @var TranslatedArticleFinder
     */
    private $articleFinder;

    /**
     * If true all unrelated articles get removed.
     *
     * @var bool
     */
    private $articleCleanup;

    /**
     * Article cleaner.
     *
     * @var I18nPageArticleCleaner
     */
    private $articleCleaner;

    /**
     * PageDcaListener constructor.
     *
     * @param Manager                 $dcaManager        Data container definition manager.
     * @param RepositoryManager       $repositoryManager Repository manager.
     * @param BackendUser             $user              Backend user.
     * @param TranslatedArticleFinder $articleFinder     Article finder.
     * @param I18nPageArticleCleaner  $articleCleaner    Article cleaner.
     * @param bool                    $articleCleanup    If true all unrelated articles get removed.
     */
    public function __construct(
        Manager $dcaManager,
        RepositoryManager $repositoryManager,
        BackendUser $user,
        TranslatedArticleFinder $articleFinder,
        I18nPageArticleCleaner $articleCleaner,
        bool $articleCleanup
    ) {
        parent::__construct($dcaManager);

        $this->repositoryManager = $repositoryManager;
        $this->user              = $user;
        $this->articleFinder     = $articleFinder;
        $this->articleCleanup    = $articleCleanup;
        $this->articleCleaner = $articleCleaner;
    }

    /**
     * Initialize the palette.
     *
     * @param DataContainer $dataContainer Data container driver.
     *
     * @return void
     */
    public function initializePalette($dataContainer): void
    {
        $definition = $this->getDefinition();
        $definition->set(['palettes', 'i18n_regular'], $definition->get(['palettes', 'regular']));

        PaletteManipulator::create()
            ->addLegend('articles_legend', ['meta_legend', 'language_legend', 'title_legend'])
            ->addField('i18n_article_override', 'articles_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('i18n_regular', static::getName());

        if (Input::get('act') !== 'edit') {
            return;
        }

        $repository = $this->repositoryManager->getRepository(PageModel::class);
        $page       = $repository->find((int) $dataContainer->id);

        if (!$page || $page->type === 'root' || $page->type === 'i18n_regular' || $page->languageMain > 0) {
            return;
        }

        PaletteManipulator::create()
            ->addField('i18n_disable', 'expert_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('regular', static::getName());
    }

    /**
     * Get all articles of the base page as options array.
     *
     * @param DataContainer|null $dataContainer Data container driver.
     *
     * @return array
     */
    public function getBasePageArticlesOptions($dataContainer = null)
    {
        if (!$dataContainer || !$dataContainer->activeRecord) {
            return [];
        }

        /** @var Repository|ArticleModel $repository */
        $repository = $this->repositoryManager->getRepository(ArticleModel::class);
        $collection = $repository->findByPid($dataContainer->activeRecord->languageMain);

        if (!$collection) {
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
     * @return void
     *
     * @throws InvalidArgumentException When an invalid query was built.
     */
    public function createI18nArticles($dataContainer = null): void
    {
        if (!$dataContainer || !$dataContainer->activeRecord || $dataContainer->activeRecord->type !== 'i18n_regular') {
            return;
        }

        // Reload the page. Active record stores the version before the changes are applied.
        $page      = $this->repositoryManager->getRepository(PageModel::class)->find((int) $dataContainer->id);
        $modes     = $this->articleFinder->getArticleModes($page, 'override');
        $overrides = $this->articleFinder->getOverrides($page);
        $deletes   = $overrides;

        foreach (array_keys($modes) as $articleId) {
            unset($deletes[$articleId]);

            if (!isset($overrides[$articleId])) {
                $this->copyArticle($dataContainer->id, $articleId);
            }
        }

        // Remove connection between the languages. Do not delete the article.
        $articleRepository = $this->repositoryManager->getRepository(ArticleModel::class);

        foreach ($deletes as $article) {
            if ($this->articleCleanup) {
                $this->articleCleaner->deleteArticle($article, $dataContainer);
            } else {
                $article->languageMain = 0;
                $article->tstamp       = time();

                $articleRepository->save($article);
            }
        }

        // Delete all unrelated articles.
        if ($this->articleCleaner) {
            $this->articleCleaner->cleanupUnrelatedArticles($dataContainer);
        }
    }

    /**
     * Load the i18n articles value.
     *
     * It combines all hard defined definitions with the latest changes.
     *
     * @param mixed         $raw          Raw value.
     * @param DataContainer $datContainer Data container driver.
     *
     * @return array
     */
    public function loadI18nArticles($raw, $datContainer)
    {
        if (\Input::post('FORM_SUBMIT') === PageModel::getTable()) {
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
            if ($config['article'] == '') {
                continue;
            }

            if ($config['mode'] === 'exclude') {
                $values[] = $config;
            }

            if (isset($articles[$config['article']])) {
                $values[] = $config;
                unset($articles[$config['article']]);
            }
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
     * @param array  $row        Current data row.
     * @param string $href       The pre generated link.
     * @param string $label      The label.
     * @param string $title      The title attribute.
     * @param string $icon       The icon path.
     * @param string $attributes Html attributes.
     *
     * @return string
     */
    public function editArticles($row, $href, $label, $title, $icon, $attributes): string
    {
        if (!$this->user->hasAccess('article', 'modules')) {
            return '';
        }

        if (!in_array($row['type'], ['regular', 'error_403', 'error_404', 'i18n_regular'])) {
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
     *
     * @return void
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

        if (!$contentElements) {
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
