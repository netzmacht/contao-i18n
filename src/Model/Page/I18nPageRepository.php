<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Page;

use Contao\PageModel;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function array_key_exists;
use function assert;
use function in_array;

class I18nPageRepository
{
    /**
     * Set of supported i18n pages.
     *
     * @var list<string>
     */
    private array $i18nPageTypes;

    /**
     * I18n page cache for base pages.
     *
     * For each language page the base page get cached at runtime.
     *
     * @var PageModel[]|null[]
     */
    private array $basePages = [];

    /**
     * I18n page cache for translated pages.
     *
     * Each translated page of a page get cached at runtime.
     *
     * @var PageModel[][]|null[][]
     */
    private array $translatedPages = [];

    /**
     * Cache of page translations.
     *
     * @var PageModel[][]
     */
    private array $translations = [];

    /**
     * Repository manager.
     */
    private RepositoryManager $repositoryManager;

    /**
     * @param list<string>      $i18nPageTypes     Set of supported i18n pages.
     * @param RepositoryManager $repositoryManager Repository manager.
     */
    public function __construct(array $i18nPageTypes, RepositoryManager $repositoryManager)
    {
        $this->i18nPageTypes     = $i18nPageTypes;
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * Check if the given page type is an i18n page type.
     *
     * @param string $pageType The page type.
     */
    public function isI18nPage(string $pageType): bool
    {
        return in_array($pageType, $this->i18nPageTypes, true);
    }

    /**
     * Get the base page for a given page id. If the page is not an 18n page the page for the page id is returned.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     */
    public function getBasePage($page): ?PageModel
    {
        if (! $page instanceof PageModel) {
            if (array_key_exists($page, $this->basePages)) {
                return $this->basePages[$page];
            }

            $repository = $this->repositoryManager->getRepository(PageModel::class);
            $page       = $repository->find((int) $page);
        }

        if (! $page || ! $this->isI18nPage($page->type)) {
            return $page;
        }

        if (! array_key_exists($page->id, $this->basePages)) {
            $repository                 = $this->repositoryManager->getRepository(PageModel::class);
            $this->basePages[$page->id] = $repository->find((int) $page->languageMain);
        }

        return $this->basePages[$page->id];
    }

    /**
     * Get the main page of a connected language page.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     */
    public function getMainPage($page): ?PageModel
    {
        if (! $page instanceof PageModel) {
            $repository = $this->repositoryManager->getRepository(PageModel::class);
            $page       = $repository->find((int) $page);
        }

        if (! $page) {
            return null;
        }

        // Current page is already the main page.
        if ((int) $page->languageMain === 0) {
            return $page;
        }

        $repository = $this->repositoryManager->getRepository(PageModel::class);
        $page       = $repository->find((int) $page->languageMain);

        return $page;
    }

    /**
     * Get all translations of a page.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     *
     * @return PageModel[]
     */
    public function getPageTranslations($page): array
    {
        $pages    = [];
        $mainPage = $this->getMainPage($page);

        if (! $mainPage) {
            return $pages;
        }

        if (array_key_exists($mainPage->id, $this->translations)) {
            return $this->translations[$mainPage->id];
        }

        $language = $this->getPageLanguage($mainPage);
        if (! $language) {
            return $pages;
        }

        $pages[$language] = $mainPage;
        $repository       = $this->repositoryManager->getRepository(PageModel::class);

        foreach ($repository->findBy(['.languageMain = ?'], [$mainPage->id]) ?: [] as $page) {
            $language = $this->getPageLanguage($page);
            if (! $language) {
                continue;
            }

            $pages[$language] = $page;
        }

        $this->translations[$mainPage->id] = $pages;

        return $pages;
    }

    /**
     * Get the language of a page.
     *
     * @param PageModel|int|string $page The page as model or id/alias.
     */
    public function getPageLanguage($page): ?string
    {
        if (! $page instanceof PageModel) {
            $repository = $this->repositoryManager->getRepository(PageModel::class);
            $page       = $repository->find((int) $page);
        }

        if (! $page) {
            return null;
        }

        // Page with loaded details.
        if ($page->language) {
            return $page->language;
        }

        $root = $this->getRootPage($page);
        if ($root) {
            return $root->language;
        }

        return null;
    }

    /**
     * Get the translated page for a given page.
     *
     * @param PageModel|int|string $page     The page as model or id/alias.
     * @param string               $language If set a specific language is loaded. Otherwise the current language.
     */
    public function getTranslatedPage($page, $language = null): ?PageModel
    {
        $language = $language ?: $this->getCurrentLanguage();
        $page     = $this->loadTranslatedPage($page, $language);

        // No page found, return.
        if (! $page) {
            return null;
        }

        // Page translation is disabled in the page settings.
        if ($page->type !== 'i18n_regular' && $page->i18n_disable === '1') {
            return $page;
        }

        // Load root page to get language.
        $rootPage = $this->getRootPage($page);
        if ($rootPage === null) {
            return null;
        }

        if ($rootPage->language === $language) {
            return $page;
        }

        // Current page is not in the fallback language tree. Not able to find the translated page.
        if (! $rootPage->fallback || $rootPage->languageRoot > 0) {
            return null;
        }

        $repository    = $this->repositoryManager->getRepository(PageModel::class);
        $specification = new TranslatedPageSpecification((int) $page->id, $language);
        $collection    = $repository->findBySpecification($specification, ['limit' => 1]);

        if ($collection) {
            $pageModel = $collection->current();
            assert($pageModel instanceof PageModel);
            $this->translatedPages[$language][$page->id] = $pageModel;
        } else {
            $this->translatedPages[$language][$page->id] = null;
        }

        return $this->translatedPages[$language][$page->id];
    }

    /**
     * Get the root page for a page.
     *
     * @param PageModel|string|int $page The page model.
     */
    public function getRootPage($page): ?PageModel
    {
        if (! $page instanceof PageModel) {
            $repository = $this->repositoryManager->getRepository(PageModel::class);
            $page       = $repository->find((int) $page);
        }

        if ($page instanceof PageModel && $page->hofff_root_page_id > 0) {
            $repository = $this->repositoryManager->getRepository(PageModel::class);

            return $repository->find((int) $page->hofff_root_page_id);
        }

        return null;
    }

    /**
     * Get the current language.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getCurrentLanguage(): string
    {
        return $GLOBALS['TL_LANGUAGE'];
    }

    /**
     * Load a translated page and cache the results.
     *
     * @param PageModel|string|int $page     The page.
     * @param string               $language The required language.
     */
    private function loadTranslatedPage($page, string $language): ?PageModel
    {
        if (! $page instanceof PageModel) {
            if (isset($this->translatedPages[$language][$page])) {
                return $this->translatedPages[$language][$page];
            }

            $repository = $this->repositoryManager->getRepository(PageModel::class);
            $page       = $repository->find((int) $page);
        }

        if (! $page instanceof PageModel) {
            return null;
        }

        $this->translatedPages[$language][$page->id] = $page;

        return $page;
    }
}
