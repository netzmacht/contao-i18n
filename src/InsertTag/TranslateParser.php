<?php

/**
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\I18n\InsertTag;

use Contao\PageModel;
use ContaoCommunityAlliance\Translator\TranslatorInterface;
use DependencyInjection\Container\PageProvider;
use Netzmacht\Contao\I18n\I18n;
use Netzmacht\Contao\Toolkit\InsertTag\Parser;
use Netzmacht\Contao\Toolkit\InsertTag\Replacer;

/**
 * Translate parser handles the translate insert tag and its shortcut "t".
 *
 * Following syntax is supported:
 *
 * {{t::path.to.translation}}
 * Try to get the translation from the page_ALIAS domain. Fallback to website domain if not translated. If the page
 * type is an i18n page type, the alias of the base page is used instead.
 *
 * If no page alias is given, the page id is used instead. Folder aliases get escaped to underscores.
 *
 * {[t::domain:path.to.translation}}
 * Translate from a given domain.
 *
 * Note: The dot syntax is used for the array structure of the language file.
 *
 * @package Netzmacht\Contao\I18n\InsertTag
 */
class TranslateParser implements Parser
{
    /**
     * I18n service.
     *
     * @var I18n
     */
    private $i18n;

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Insert tag replacer.
     *
     * @var Replacer
     */
    private $replacer;

    /**
     * Page provider.
     *
     * @var PageProvider
     */
    private $pageProvider;

    /**
     * TranslateParser constructor.
     *
     * @param I18n                $i18n         I18n service.
     * @param TranslatorInterface $translator   Translator.
     * @param PageProvider        $pageProvider Page provider.
     * @param Replacer            $replacer     Insert tag replacer.
     */
    public function __construct(
        I18n $i18n,
        TranslatorInterface $translator,
        PageProvider $pageProvider,
        Replacer $replacer
    ) {
        $this->i18n         = $i18n;
        $this->translator   = $translator;
        $this->replacer     = $replacer;
        $this->pageProvider = $pageProvider;
    }

    /**
     * {@inheritDoc}
     */
    public static function getTags()
    {
        return ['t', 'translate'];
    }

    /**
     * {@inheritDoc}
     */
    public function supports($tag)
    {
        return in_array($tag, static::getTags());
    }

    /**
     * Get the page.
     *
     * @return PageModel|null
     */
    private function getPage()
    {
        $page = $this->pageProvider->getPage();
        if ($page) {
            return $this->i18n->getBasePage($page) ?: $page;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function parse($raw, $tag, $params = null, $cache = true)
    {
        if ($params === null) {
            return '';
        }

        list($domain, $key) = explode(':', $params, 2);

        if ($key === null) {
            $key  = $domain;
            $page = $this->getPage();

            if ($page) {
                $domain = 'page_';

                if ($page->alias) {
                    $domain .= str_replace('/', '_', $page->alias);
                } else {
                    $domain .= $page->id;
                }
            } else {
                $domain = 'website';
            }
        }

        $result = $this->translator->translate($key, $domain);

        // Fallback to global website domain.
        if ($domain !== 'website' && $result === $key) {
            $result = $this->translator->translate($key, 'website');
        }

        // Translator won't check if content is a string.
        if (is_array($result)) {
            return false;
        }

        return $this->replacer->replace($result, $cache);
    }
}
