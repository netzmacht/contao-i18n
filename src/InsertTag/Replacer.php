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

/**
 * Class Replacer.
 *
 * @package Netzmacht\Contao\I18n\InsertTag
 */
class Replacer
{
    /**
     * Insert tag map.
     *
     * @var Parser[]
     */
    private $parsers;

    /**
     * Replacer constructor.
     *
     * @param Parser[] $parsers Insert tag parsers.
     */
    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }

    /**
     * Get the replacer.
     *
     * This method is only designed to solve Contao lack of DI support! Don't use it. Use the service instead!
     *
     * @return static
     * @internal
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getInstance()
    {
        return $GLOBALS['container']['i18n.insert-tag.replacer'];
    }

    /**
     * Replace insert tags.
     *
     * @param string    $tag   The tag name.
     * @param bool|true $cache Generate for the cache.
     *
     * @return bool|string
     */
    public function replace($tag, $cache = true)
    {
        list($tag, $params) = explode('::', $tag, 2);

        foreach ($this->parsers as $parser) {
            if ($parser->supports($tag)) {
                return $parser->parse($params, $cache);
            }
        }

        return false;
    }
}
