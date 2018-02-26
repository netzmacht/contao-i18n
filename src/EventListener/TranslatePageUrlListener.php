<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface as ContaoFramework;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\I18n\Context\TranslatePageUrlContext;
use Netzmacht\Contao\I18n\I18n;

/**
 * Class TranslatePageUrlListener
 */
class TranslatePageUrlListener
{
    /**
     * The i18n service.
     *
     * @var I18n
     */
    private $i18n;

    /**
     * The contao framework.
     *
     * @var ContaoFramework
     */
    private $framework;

    /**
     * GetFrontendUrlListener constructor.
     *
     * @param I18n            $i18n      The i18n service.
     * @param ContaoFramework $framework The contao framework.
     */
    public function __construct(I18n $i18n, ContaoFramework $framework)
    {
        $this->i18n      = $i18n;
        $this->framework = $framework;
    }

    /**
     * Handle the generateFrontendUrl hook to translate a page url.
     *
     * @param array  $page   Given page as array.
     * @param string $params Optional query params.
     * @param string $url    Url of the current page.
     *
     * @return string
     */
    public function onGenerateFrontendUrl(array $page, $params, $url): string
    {
        $context = new TranslatePageUrlContext();

        if ($this->i18n->matchCurrentContext($context)) {
            return $url;
        }

        if ($this->i18n->matchCurrentContext(new FrontendModuleContext('changelanguage', 0))) {
            return $url;
        }

        $translatedPage = $this->i18n->getTranslatedPage($page['id']);
        if ($translatedPage && $translatedPage->id != $page['id']) {
            $this->i18n->enterContext($context);
            $url = $translatedPage->getFrontendUrl($params);
            $this->i18n->leaveContext($context);
        }

        return $url;
    }
}
