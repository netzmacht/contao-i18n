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

use ModuleNavigation;
use Netzmacht\Contao\I18n\I18nTrait;
use PageModel;

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
     * Translated page of the redirect page.
     *
     * @var PageModel|null
     */
    private $translatedPage;

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

        if ($this->defineRoot && $this->rootPage > 0) {
            $this->translatedPage = $i18n->getTranslatedPage($this->rootPage);

            if ($this->translatedPage) {
                $this->rootPage = $this->translatedPage->id;
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
        if ($this->translatedPage) {
            if ($this->translatedPage->hofff_root_page_id != $this->currentPage->hofff_root_page_id
                && $this->translatedPage->domain != ''
                && $this->translatedPage->domain != $this->currentPage->domain
            ) {
                $host = $this->translatedPage->domain;
            }
        }

        return parent::renderNavigation($pid, $level, $host, $language);
    }
}
