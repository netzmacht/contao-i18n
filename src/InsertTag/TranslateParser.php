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

use Contao\InsertTags;
use Contao\PageModel;
use ContaoCommunityAlliance\Translator\TranslatorInterface;
use Netzmacht\Contao\I18n\I18n;

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
     * Current page.
     *
     * @var PageModel
     */
    private $page;

    /**
     * Contao insert tag replacer.
     *
     * @var InsertTags
     */
    private $insertTags;

    /**
     * TranslateParser constructor.
     *
     * @param I18n                $i18n       I18n service.
     * @param TranslatorInterface $translator Translator.
     * @param PageModel           $page       Current page.
     * @param InsertTags          $insertTags Insert tags.
     */
    public function __construct(I18n $i18n, TranslatorInterface $translator, PageModel $page, InsertTags $insertTags)
    {
        $this->i18n       = $i18n;
        $this->translator = $translator;
        $this->page       = $i18n->getBasePage($page) ?: $page;
        $this->insertTags = $insertTags;
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
     * {@inheritDoc}
     */
    public function parse($params = null, $cache = true)
    {
        if ($params === null) {
            return '';
        }

        list($domain, $key) = explode(':', $params, 2);

        if ($key === null) {
            $key = $domain;

            if ($this->page) {
                $domain = 'page_';

                if ($this->page->alias) {
                    $domain .= str_replace('/', '_', $this->page->alias);
                } else {
                    $domain .= $this->page->id;
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

        return $this->insertTags->replace($result, $cache);
    }
}
