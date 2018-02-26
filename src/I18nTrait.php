<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\I18n;

use Contao\System;

/**
 * Class I18nTrait.
 *
 * @package Netzmacht\Contao\I18n
 */
trait I18nTrait
{
    /**
     * Get the I18n service.
     *
     * @return I18n
     */
    protected static function getI18n(): I18n
    {
        return System::getContainer()->get('netzmacht.contao_i18n.i18n');
    }
}
