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

use Contao\Model\Registry;
use Netzmacht\Contao\I18n\I18n;
use Netzmacht\Contao\I18n\Router;

$container['i18n.pages'] = ['i18n_regular'];

$container['i18n'] = $container->share(
    function ($container) {
        return new I18n($container['i18n.pages'], $container['database.connection'], Registry::getInstance());
    }
);

$container['i18n.router'] = $container->share(
    function ($container) {
        $types = empty($GLOBALS['I18N_ROUTING']) ? [] : (array) $GLOBALS['I18N_ROUTING'];

        return new Router($container['i18n'], $types);
    }
);
