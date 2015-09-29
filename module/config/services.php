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

global $container;

use Netzmacht\Contao\I18n\I18n;

$container['i18n.pages'] = ['i18n_regular'];

$container['i18n'] = $container->share(
    function ($container) {
        return new I18n($container['i18n.pages']);
    }
);
