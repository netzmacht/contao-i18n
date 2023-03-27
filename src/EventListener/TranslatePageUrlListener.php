<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\I18n\Context\TranslatePageUrlContext;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;

final class TranslatePageUrlListener
{
    public function __construct(private I18nPageRepository $i18nPageRepository, private ContextStack $contextStack)
    {
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

        if ($this->contextStack->matchCurrentContext(FrontendModuleContext::ofType('changelanguage'))) {
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
