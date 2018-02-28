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
use Patchwork\Utf8;

/**
 * Class I18nCustomNavigation
 */
class I18nCustomNavigation extends Module
{
    /**
     * Template name.
     *
     * @var string
     */
    protected $strTemplate = 'mod_customnav';

    /**
     * Redirect to the selected page
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            /** @var BackendTemplate|object $template */
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['i18n_customnav'][0]) . ' ###';
            $template->title    = $this->headline;
            $template->id       = $this->id;
            $template->link     = $this->name;
            $template->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $template->parse();
        }

        // Always return an array (see #4616)
        $this->pages = StringUtil::deserialize($this->pages, true);

        if (empty($this->pages) || $this->pages[0] == '') {
            return '';
        }

        $strBuffer = parent::generate();

        return ($this->Template->items != '') ? $strBuffer : '';
    }

    /**
     * Generate the module.
     *
     * @return void
     *
     * @throws \Exception If the jumpTo relation is broken.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function compile()
    {
        $items  = [];
        $groups = $this->getUserGroups();

        // Get all active pages
        $objPages = PageModel::findPublishedRegularWithoutGuestsByIds($this->pages);

        // Return if there are no pages
        if ($objPages === null) {
            return;
        }

        $translatedPages = $this->loadTranslatedPages($objPages);
        $arrPages        = $this->preparePagesOrder($translatedPages);

        // Set default template
        if ($this->navigationTpl == '') {
            $this->navigationTpl = 'nav_default';
        }

        /** @var FrontendTemplate|object $objTemplate */
        $objTemplate = new FrontendTemplate($this->navigationTpl);

        $objTemplate->type  = \get_class($this);
        $objTemplate->cssID = $this->cssID;
        $objTemplate->level = 'level_1';

        /** @var PageModel[] $arrPages */
        foreach ($arrPages as $objModel) {
            $userGroups = StringUtil::deserialize($objModel->groups);

            // Do not show protected pages unless a front end user is logged in
            if (!$objModel->protected
                || (\is_array($userGroups) && \count(array_intersect($userGroups, $groups)))
                || $this->showProtected
            ) {
                // Get href
                $items[] = $this->compileItem($objModel, $GLOBALS['objPage']);
            }
        }

        // Add classes first and last
        $items[0]['class']     = trim($items[0]['class'] . ' first');
        $last                  = (\count($items) - 1);
        $items[$last]['class'] = trim($items[$last]['class'] . ' last');

        $objTemplate->items = $items;

        $this->Template->request        = Environment::get('indexFreeRequest');
        $this->Template->skipId         = 'skipNavigation' . $this->id;
        $this->Template->skipNavigation = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['skipNavigation']);
        $this->Template->items          = !empty($items) ? $objTemplate->parse() : '';
    }

    /**
     * The the user groups of the current user.
     *
     * @return array
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
     * @param array $objPages Pages definition.
     *
     * @return array
     */
    protected function loadTranslatedPages($objPages): array
    {
        $repository      = $this->getContainer()->get('netzmacht.contao_i18n.page_repository');
        $translatedPages = [];

        foreach ($objPages as $index => $page) {
            $translatedPages[$index] = $repository->getTranslatedPage($page) ?: $page;
        }

        return $translatedPages;
    }

    /**
     * Prepare the order of the pages.
     *
     * @param PageModel[]|array $translatedPages Translated pages.
     *
     * @return array
     */
    protected function preparePagesOrder(array $translatedPages): array
    {
        $pages = [];

        // Sort the array keys according to the given order
        if ($this->orderPages != '') {
            $tmp = StringUtil::deserialize($this->orderPages);

            if (!empty($tmp) && \is_array($tmp)) {
                $pages = array_map(
                    function () {
                    },
                    array_flip($tmp)
                );
            }
        }

        // Add the items to the pre-sorted array
        foreach ($translatedPages as $page) {
            $pages[$page->id] = $page;
        }

        $pages = array_values(array_filter($pages));

        return $pages;
    }

    /**
     * Compile an item.
     *
     * @param PageModel                            $objModel The page model.
     * @param PageRegular|PageRedirect|PageForward $objPage  The current page object.
     *
     * @throws \Exception In a model relation could not be handled.
     *
     * @return array
     */
    protected function compileItem($objModel, $objPage): array
    {
        $href  = $this->buildHref($objModel);
        $trail = \in_array($objModel->id, $objPage->trail);

        // Active page
        if ($objPage->id == $objModel->id && $href == Environment::get('request')) {
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
            if ($objModel->type == 'redirect' && $objModel->target) {
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
        if ($objModel->type == 'redirect' && $objModel->target) {
            $row['target'] = ' target="_blank"';
        }

        return $row;
    }

    /**
     * Build the href of the page.
     *
     * @param PageModel $objModel The current page.
     *
     * @return string
     *
     * @throws \Exception If any model relation could not be handled.
     */
    protected function buildHref($objModel): string
    {
        switch ($objModel->type) {
            case 'redirect':
                $href = $objModel->url;
                break;

            case 'forward':
                if (($objNext = $objModel->getRelated('jumpTo')) instanceof PageModel
                    || ($objNext = PageModel::findFirstPublishedRegularByPid($objModel->id)) instanceof PageModel
                ) {
                    /** @var PageModel $objNext */
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
}
