<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Module;

use Contao\BackendTemplate;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\PageForward;
use Contao\PageModel;
use Contao\PageRedirect;
use Contao\PageRegular;
use Contao\StringUtil;
use Contao\System;

use function array_filter;
use function array_flip;
use function array_intersect;
use function array_map;
use function array_values;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function str_replace;
use function strncmp;
use function trim;

class I18nCustomNavigation extends Module
{
    /**
     * Template name.
     *
     * @var string
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $strTemplate = 'mod_customnav';

    /**
     * Redirect to the selected page
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['i18n_customnav'][0] . ' ###';
            $template->title    = $this->headline;
            $template->id       = $this->id;
            $template->link     = $this->name;
            $template->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $template->parse();
        }

        // Always return an array (see #4616)
        $this->pages = array_filter(StringUtil::deserialize($this->pages, true));

        if (empty($this->pages)) {
            return '';
        }

        $strBuffer = parent::generate();

        return $this->Template->items !== '' ? $strBuffer : '';
    }

    /**
     * Generate the module.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function compile(): void
    {
        $items  = [];
        $groups = $this->getUserGroups();

        // Get all active pages
        $objPages = PageModel::findPublishedRegularWithoutGuestsByIds($this->pages);

        // Return if there are no pages
        if ($objPages === null) {
            return;
        }

        $translatedPages = $this->loadTranslatedPages($objPages->getModels());
        $arrPages        = $this->preparePagesOrder($translatedPages);

        // Set default template
        if ($this->navigationTpl === '') {
            $this->navigationTpl = 'nav_default';
        }

        $objTemplate = new FrontendTemplate($this->navigationTpl);

        $objTemplate->type  = get_class($this);
        $objTemplate->cssID = $this->cssID;
        $objTemplate->level = 'level_1';

        foreach ($arrPages as $objModel) {
            $userGroups = StringUtil::deserialize($objModel->groups);

            // Do not show protected pages unless a front end user is logged in
            if (
                $objModel->protected
                && ! (is_array($userGroups) && count(array_intersect($userGroups, $groups)))
                && ! $this->showProtected
            ) {
                continue;
            }

            // Get href
            $items[] = $this->compileItem($objModel, $GLOBALS['objPage']);
        }

        $items              = $this->addCssClasses($items);
        $objTemplate->items = $items;

        $this->Template->request        = Environment::get('indexFreeRequest');
        $this->Template->skipId         = 'skipNavigation' . $this->id;
        $this->Template->skipNavigation = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['skipNavigation']);
        $this->Template->items          = ! empty($items) ? $objTemplate->parse() : '';
    }

    /**
     * The user groups of the current user.
     *
     * @return array<int|numeric-string>
     */
    protected function getUserGroups(): array
    {
        $groups = [];

        // Get all groups of the current front end user
        if (FE_USER_LOGGED_IN) {
            $this->import('FrontendUser', 'User');
            $groups = $this->User->groups;
        }

        return $groups;
    }

    /**
     * Load all translated pages.
     *
     * @param list<PageModel> $objPages Pages definition.
     *
     * @return array<int, PageModel>
     */
    protected function loadTranslatedPages(array $objPages): array
    {
        $currentPage = $this->getContainer()->get('netzmacht.contao_i18n.page_provider')->getPage();
        $repository  = $this->getContainer()->get('netzmacht.contao_i18n.page_repository');
        $rootPage    = $repository->getRootPage($currentPage);

        // We are in the root language. No translation needed.
        if ($rootPage && $rootPage->fallback && $rootPage->languageRoot === '') {
            return $objPages;
        }

        $translatedPages = [];

        foreach ($objPages as $index => $page) {
            $page = $repository->getTranslatedPage($page);
            if (! $page) {
                continue;
            }

            $translatedPages[$index] = $page;
        }

        return $translatedPages;
    }

    /**
     * Prepare the order of the pages.
     *
     * @param PageModel[] $translatedPages Translated pages.
     *
     * @return list<PageModel>
     */
    protected function preparePagesOrder(array $translatedPages): array
    {
        $pages = [];

        // Sort the array keys according to the given order
        if (! empty($this->orderPages)) {
            $tmp = StringUtil::deserialize($this->orderPages);

            if (! empty($tmp) && is_array($tmp)) {
                $pages = array_map(
                    static function (): void {
                    },
                    array_flip($tmp)
                );
            }
        }

        // Add the items to the pre-sorted array
        foreach ($translatedPages as $page) {
            $pages[$page->languageMain > 0 ? $page->languageMain : $page->id] = $page;
        }

        return array_values(array_filter($pages));
    }

    /**
     * Compile an item.
     *
     * @param PageModel                            $objModel The page model.
     * @param PageRegular|PageRedirect|PageForward $objPage  The current page object.
     *
     * @return array<string,mixed>
     */
    protected function compileItem($objModel, $objPage): array
    {
        $href  = $this->buildHref($objModel);
        $trail = in_array($objModel->id, $objPage->trail);

        // Active page
        if ((int) $objPage->id === (int) $objModel->id && $href === Environment::get('request')) {
            $strClass = trim($objModel->cssClass);
            $row      = $objModel->row();

            $row['isActive']    = true;
            $row['isTrail']     = false;
            $row['class']       = trim('active ' . $strClass);
            $row['title']       = StringUtil::specialchars($objModel->title, true);
            $row['pageTitle']   = StringUtil::specialchars($objModel->pageTitle, true);
            $row['link']        = $objModel->title;
            $row['href']        = $href;
            $row['nofollow']    = (strncmp($objModel->robots, 'noindex,nofollow', 16) === 0);
            $row['target']      = '';
            $row['description'] = str_replace(["\n", "\r"], [' ', ''], $objModel->description);

            // Override the link target
            if ($objModel->type === 'redirect' && $objModel->target) {
                $row['target'] = ' target="_blank"';
            }

            return $row;
        }

        // Regular page
        $strClass = trim($objModel->cssClass . ($trail ? ' trail' : ''));
        $row      = $objModel->row();

        $row['isActive']    = false;
        $row['isTrail']     = $trail;
        $row['class']       = $strClass;
        $row['title']       = StringUtil::specialchars($objModel->title, true);
        $row['pageTitle']   = StringUtil::specialchars($objModel->pageTitle, true);
        $row['link']        = $objModel->title;
        $row['href']        = $href;
        $row['nofollow']    = (strncmp($objModel->robots, 'noindex,nofollow', 16) === 0);
        $row['target']      = '';
        $row['description'] = str_replace(["\n", "\r"], [' ', ''], $objModel->description);

        // Override the link target
        if ($objModel->type === 'redirect' && $objModel->target) {
            $row['target'] = ' target="_blank"';
        }

        return $row;
    }

    /**
     * Build the href of the page.
     *
     * @param PageModel $objModel The current page.
     */
    protected function buildHref(PageModel $objModel): string
    {
        switch ($objModel->type) {
            case 'redirect':
                $href = $objModel->url;
                break;

            case 'forward':
                $objNext = $objModel->getRelated('jumpTo');
                if (! $objNext instanceof PageModel) {
                    $objNext = PageModel::findFirstPublishedRegularByPid($objModel->id);
                }

                if ($objNext instanceof PageModel) {
                    $href = $objNext->getFrontendUrl();
                    break;
                }
            // DO NOT ADD A break; STATEMENT
            default:
                $href = $objModel->getFrontendUrl();
                break;
        }

        return $href;
    }

    /**
     * Add css classes.
     *
     * @param list<array<string,mixed>> $items Items.
     *
     * @return list<array<string,mixed>>
     */
    private function addCssClasses(array $items): array
    {
        if ($items) {
            // Add classes first and last
            $items[0]['class']     = trim($items[0]['class'] . ' first');
            $last                  = count($items) - 1;
            $items[$last]['class'] = trim($items[$last]['class'] . ' last');
        }

        return $items;
    }
}
