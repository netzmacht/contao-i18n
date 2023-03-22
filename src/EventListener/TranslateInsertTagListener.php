<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\I18n\PageProvider\PageProvider;
use Netzmacht\Contao\Toolkit\InsertTag\AbstractInsertTagParser;
use Symfony\Contracts\Translation\TranslatorInterface as Translator;

use function array_pad;
use function explode;
use function in_array;

class TranslateInsertTagListener extends AbstractInsertTagParser
{
    private I18nPageRepository $i18nPageRepository;

    private PageProvider $pageProvider;

    private Translator $translator;

    public function __construct(
        I18nPageRepository $i18nPageRepository,
        PageProvider $pageProvider,
        Translator $translator
    ) {
        $this->i18nPageRepository = $i18nPageRepository;
        $this->pageProvider       = $pageProvider;
        $this->translator         = $translator;
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function supports(string $tag, bool $cache): bool
    {
        return in_array($tag, ['t', 'translate']);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseArguments(string $query): array
    {
        if ($query === '') {
            return [
                'domain' => null,
                'key'    => null,
            ];
        }

        [$domain, $key] = array_pad(explode(':', $query, 2), 2, null);

        if ($key === null) {
            $pageAlias = $this->getPageAlias();
            $key       = $domain;
            $domain    = $pageAlias ? 'contao_page_' . $pageAlias : 'contao_website';
        }

        return [
            'domain' => $domain,
            'key'    => $key,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function parseTag(array $arguments, string $tag, string $raw)
    {
        $translated = $this->translator->trans($arguments['key'], [], $arguments['domain']);

        if ($arguments['domain'] !== 'contao_website' && $translated === $arguments['key']) {
            $translated = $this->translator->trans($arguments['key'], [], 'contao_website');
        }

        return $translated;
    }

    /**
     * Get the page alias of the current page.
     */
    private function getPageAlias(): ?string
    {
        $page = $this->getPage();

        if ($page) {
            return $page->alias;
        }

        return null;
    }

    /**
     * Get the page.
     */
    private function getPage(): ?PageModel
    {
        $page = $this->pageProvider->getPage();
        if ($page) {
            return $this->i18nPageRepository->getBasePage($page) ?: $page;
        }

        return null;
    }
}
