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
 * Class TranslatePageUrlContext
 *
 * @package Netzmacht\Contao\I18n\Context
 */
final class TranslatePageUrlContext implements Context
{
    /**
     * {@inheritdoc}
     */
    public function match(Context $context, bool $strict = false): bool
    {
        return $context instanceof TranslatePageUrlContext;
    }
}
