<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */
namespace Netzmacht\Contao\I18n\Module;

use Contao\ModuleNavigation;
use Contao\PageModel;
use Netzmacht\Contao\I18n\I18nTrait;

/**
 * The i18n navigation module sets the defined root page of the navigation to the related base page.
 *
 * @property string|int  rootPage   The root page.
 * @property string|bool defineRoot If true a root page should be used.
 */
class Navigation extends ModuleNavigation
{
    use I18nTrait;

    /**
     * Base page is the base page of an i18n page.
     *
     * @var PageModel|null
     */
    private $basePage;

    /**
     * Current page.
     *
     * @var PageModel|null
     */
    private $currentPage;

    /**
     * {@inheritDoc}
     */
    protected function compile()
    {
        $i18n = $this->getI18n();

        $this->currentPage = $this->getServiceContainer()->getPageProvider()->getPage();

        if ($i18n->isI18nPage($this->currentPage->type) && $this->defineRoot && $this->rootPage > 0) {
            $this->basePage = $i18n->getBasePage($this->rootPage);

            if ($this->basePage) {
                $this->rootPage = $this->basePage->id;
            }
        }

        parent::compile();
    }

    /**
     * {@inheritDoc}
     */
    protected function renderNavigation($pid, $level = 1, $host = null, $language = null)
    {
        // We have to reset the host here.
        if ($this->basePage) {
            if ($this->basePage->rootId != $this->currentPage->rootId
                && $this->basePage->domain != ''
                && $this->basePage->domain != $this->currentPage->domain
            ) {
                $host = $this->basePage->domain;
            }
        }

        return parent::renderNavigation($pid, $level, $host, $language);
    }
}
