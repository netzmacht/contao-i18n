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
 * The parser pares an insert tag.
 *
 * @package Netzmacht\Contao\I18n\InsertTag
 */
interface Parser
{
    /**
     * Get the supported tags.
     *
     * @return array
     */
    public static function getTags();

    /**
     * Parse an insert tag.
     *
     * @param string    $params Insert tag params.
     * @param bool|true $cache  Check if the insert tags get cached.
     *
     * @return string
     */
    public function parse($params = null, $cache = true);

    /**
     * Check if an tag is supported.
     *
     * @param string $tag Tag name.
     *
     * @return bool
     */
    public function supports($tag);
}
