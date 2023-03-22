<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\I18n\Context\TranslatePageUrlContext;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;

class TranslatePageUrlListener
{
    private I18nPageRepository $i18nPageRepository;

    private ContextStack $contextStack;

    public function __construct(I18nPageRepository $i18nPageRepository, ContextStack $contextStack)
    {
        $this->i18nPageRepository = $i18nPageRepository;
        $this->contextStack       = $contextStack;
    }

    /**
     * Handle the generateFrontendUrl hook to translate a page url.
     *
     * @param array<string,mixed> $page   Given page as array.
     * @param string              $params Optional query params.
     * @param string              $url    Url of the current page.
     */
    public function onGenerateFrontendUrl(array $page, string $params, string $url): string
    {
        $context = new TranslatePageUrlContext();

        if ($this->contextStack->matchCurrentContext($context)) {
            return $url;
        }

        if ($this->contextStack->matchCurrentContext(new FrontendModuleContext('changelanguage', 0))) {
            return $url;
        }

        $translatedPage = $this->i18nPageRepository->getTranslatedPage($page['id']);
        if ($translatedPage && (int) $translatedPage->id !== (int) $page['id']) {
            $this->contextStack->enterContext($context);
            $url = $translatedPage->getFrontendUrl($params);
            $this->contextStack->leaveContext($context);
        }

        return $url;
    }
}
