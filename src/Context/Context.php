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

namespace Netzmacht\Contao\I18n\Context;

/**
 * Interface Context
 */
interface Context
{
    /**
     * Compare two contexts.
     *
     * @param Context $context Check context to compare with.
     * @param bool    $strict  Some contexts may provide a strict mode to compare.
     *
     * @return bool
     */
    public function match(Context $context, bool $strict = false): bool;
}
