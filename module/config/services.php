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

use Contao\InsertTags;
use Netzmacht\Contao\I18n\I18n;
use Netzmacht\Contao\I18n\InsertTag\Replacer;
use Netzmacht\Contao\I18n\InsertTag\TranslateParser;

$container['i18n.pages'] = ['i18n_regular'];

$container['i18n'] = $container->share(
    function ($container) {
        return new I18n($container['i18n.pages']);
    }
);

if (!isset($container['i18n.insert-tags.parsers'])) {
    $container['i18n.insert-tags.parsers'] = new \ArrayObject();
}

$container['i18n.insert-tags.parsers'][] = 'i18n.insert-tags.parsers.translate';

$container['i18n.insert-tags.parsers.translate'] = function ($container) {
    $page = $container['page-provider']->getPage();

    return new TranslateParser($container['i18n'], $container['translator'], $page, new InsertTags());
};

$container['i18n.insert-tags.replacer'] = $container->share(
    function ($container) {
        $parsers = array_map(
            function ($service) use ($container) {
                return $container[$service];
            },
            $container['i18n.insert-tags.parsers']->getArrayCopy()
        );

        return new Replacer($parsers);
    }
);
