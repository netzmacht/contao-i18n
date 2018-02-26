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

use Contao\PageModel;
use Netzmacht\Contao\I18n\I18n;
use Netzmacht\Contao\I18n\PageProvider\PageProvider;
use Netzmacht\Contao\Toolkit\InsertTag\AbstractInsertTagParser;
use Symfony\Component\Translation\TranslatorInterface as Translator;

/**
 * Class TranslateInsertTagListener
 */
class TranslateInsertTagListener extends AbstractInsertTagParser
{
    /**
     * I18n service.
     *
     * @var I18n
     */
    private $i18n;

    /**
     * Page provider.
     *
     * @var PageProvider
     */
    private $pageProvider;

    /**
     * Translator.
     *
     * @var Translator
     */
    private $translator;

    /**
     * TranslateInsertTagListener constructor.
     *
     * @param I18n         $i18n         I18n service.
     * @param PageProvider $pageProvider Current page provider.
     * @param Translator   $translator   Translator.
     */
    public function __construct(I18n $i18n, PageProvider $pageProvider, Translator $translator)
    {
        $this->i18n         = $i18n;
        $this->pageProvider = $pageProvider;
        $this->translator   = $translator;
    }

    /**
     * {@inheritdoc}
     */
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
                'key'    => null
            ];
        }

        list($domain, $key) = explode(':', $query, 2);

        if ($key === null) {
            $pageAlias = $this->getPageAlias();
            $key    = $domain;
            $domain = $pageAlias ? 'contao_page_' . $pageAlias : 'contao_website';
        }

        return [
            'domain' => $domain,
            'key'    => $key
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function parseTag(array $arguments, string $tag, string $raw): ?string
    {
        $translated = $this->translator->trans($arguments['key'], [], $arguments['domain']);

        if ($arguments['domain'] !== 'contao_website' && $translated === $arguments['key']) {
            $translated = $this->translator->trans($arguments['key'],[], 'contao_website');
        }

        if (is_array($translated)) {
            return null;
        }

        return (string) $translated;
    }

    /**
     * @return null|string
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
     *
     * @return PageModel|null
     */
    private function getPage(): ?PageModel
    {
        $page = $this->pageProvider->getPage();
        if ($page) {
            return $this->i18n->getBasePage($page) ?: $page;
        }

        return null;
    }
}
