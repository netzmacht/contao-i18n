<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Module;

use Contao\ModuleNavigation;
use Contao\PageModel;

/**
 * The i18n navigation module sets the defined root page of the navigation to the related base page.
 *
 * @property string|int  rootPage   The root page.
 * @property string|bool defineRoot If true a root page should be used.
 */
class I18nNavigation extends ModuleNavigation
{
    /**
     * Translated page of the redirect page.
     */
    private ?PageModel $translatedPage = null;

    /**
     * Current page.
     */
    private ?PageModel $currentPage = null;

    protected function compile(): void
    {
        $i18n         = static::getContainer()->get('netzmacht.contao_i18n.page_repository');
        $pageProvider = $this->getContainer()->get('netzmacht.contao_i18n.page_provider');

        $this->currentPage = $pageProvider->getPage();

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
    protected function renderNavigation($pid, $level = 1, $host = null, $language = null): string
    {
        // We have to reset the host here.
        if ($this->translatedPage) {
            if (
                (int) $this->translatedPage->hofff_root_page_id !== (int) $this->currentPage->hofff_root_page_id
                && $this->translatedPage->domain !== ''
                && $this->translatedPage->domain !== $this->currentPage->domain
            ) {
                $host = $this->translatedPage->domain;
            }
        }

        return parent::renderNavigation($pid, $level, $host, $language);
    }
}
