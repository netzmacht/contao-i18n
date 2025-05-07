<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Page;

use Contao\PageModel;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function array_key_exists;
use function assert;
use function in_array;

final class I18nPageRepository
{
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
     * @param list<string>      $i18nPageTypes     Set of supported i18n pages.
     * @param RepositoryManager $repositoryManager Repository manager.
     */
    public function __construct(
        private readonly array $i18nPageTypes,
        private readonly RepositoryManager $repositoryManager,
    ) {
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
     * @param int|string|PageModel $page The page as model or id/alias.
     */
    public function getBasePage(PageModel|int|string $page): PageModel|null
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
     * @param int|string|PageModel $page The page as model or id/alias.
     */
    public function getMainPage(PageModel|int|string $page): PageModel|null
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

        return $repository->find((int) $page->languageMain);
    }

    /**
     * Get all translations of a page.
     *
     * @param int|string|PageModel $page The page as model or id/alias.
     *
     * @return PageModel[]
     */
    public function getPageTranslations(PageModel|int|string $page): array
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
        if ($language === null) {
            return $pages;
        }

        $pages[$language] = $mainPage;
        $repository       = $this->repositoryManager->getRepository(PageModel::class);

        foreach ($repository->findBy(['.languageMain = ?'], [$mainPage->id]) ?? [] as $translatedPage) {
            assert($translatedPage instanceof PageModel);

            $language = $this->getPageLanguage($translatedPage);
            if ($language === null) {
                continue;
            }

            $pages[$language] = $translatedPage;
        }

        $this->translations[$mainPage->id] = $pages;

        return $pages;
    }

    /**
     * Get the language of a page.
     *
     * @param int|string|PageModel $page The page as model or id/alias.
     */
    public function getPageLanguage(PageModel|int|string $page): string|null
    {
        if (! $page instanceof PageModel) {
            $repository = $this->repositoryManager->getRepository(PageModel::class);
            $page       = $repository->find((int) $page);
        }

        if (! $page) {
            return null;
        }

        // Page with loaded details.
        if ($page->rootLanguage !== null) {
            return $page->rootLanguage;
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
     * @param int|string|PageModel $page     The page as model or id/alias.
     * @param string|null          $language If set a specific language is loaded. Otherwise the current language.
     */
    public function getTranslatedPage(PageModel|int|string $page, string|null $language = null): PageModel|null
    {
        $language ??= $this->getCurrentLanguage();
        $page       = $this->loadTranslatedPage($page, $language);

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
     * @param int|string|PageModel $page The page model.
     */
    public function getRootPage(PageModel|int|string $page): PageModel|null
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
     * @param int|string|PageModel $page     The page.
     * @param string               $language The required language.
     */
    private function loadTranslatedPage(PageModel|int|string $page, string $language): PageModel|null
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
