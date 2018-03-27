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

use Contao\CoreBundle\Framework\ContaoFrameworkInterface as ContaoFramework;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\I18n\Context\TranslatePageUrlContext;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;

/**
 * Class TranslatePageUrlListener
 */
class TranslatePageUrlListener
{
    /**
     * I18n page repository.
     *
     * @var I18nPageRepository
     */
    private $i18nPageRepository;

    /**
     * The i18n context stack.
     *
     * @var ContextStack
     */
    private $contextStack;

    /**
     * The contao framework.
     *
     * @var ContaoFramework
     */
    private $framework;

    /**
     * GetFrontendUrlListener constructor.
     *
     * @param I18nPageRepository $i18nPageRepository The i18n page repository.
     * @param ContextStack       $contextStack       The i18n context stack.
     * @param ContaoFramework    $framework          The contao framework.
     */
    public function __construct(
        I18nPageRepository $i18nPageRepository,
        ContextStack $contextStack,
        ContaoFramework $framework
    ) {
        $this->i18nPageRepository = $i18nPageRepository;
        $this->contextStack       = $contextStack;
        $this->framework          = $framework;
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

        if ($this->contextStack->matchCurrentContext($context)) {
            return $url;
        }

        if ($this->contextStack->matchCurrentContext(new FrontendModuleContext('changelanguage', 0))) {
            return $url;
        }

        $translatedPage = $this->i18nPageRepository->getTranslatedPage($page['id']);
        if ($translatedPage && $translatedPage->id != $page['id']) {
            $this->contextStack->enterContext($context);
            $url = $translatedPage->getFrontendUrl($params);
            $this->contextStack->leaveContext($context);
        }

        return $url;
    }
}
