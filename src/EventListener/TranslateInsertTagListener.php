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

final class TranslateInsertTagListener extends AbstractInsertTagParser
{
    public function __construct(
        private readonly I18nPageRepository $i18nPageRepository,
        private readonly PageProvider $pageProvider,
        private readonly Translator $translator,
    ) {
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
    private function getPageAlias(): string|null
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
    private function getPage(): PageModel|null
    {
        $page = $this->pageProvider->getPage();
        if ($page) {
            return $this->i18nPageRepository->getBasePage($page) ?: $page;
        }

        return null;
    }
}
