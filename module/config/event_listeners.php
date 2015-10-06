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

use Netzmacht\Contao\I18n\InsertTag\TranslateParser;
use Netzmacht\Contao\Toolkit\Event\InitializeSystemEvent;

return array(
    InitializeSystemEvent::NAME => array(
        function (InitializeSystemEvent $event) {
            $container = $event->getServiceContainer();
            $replacer  = $container->getInsertTagReplacer();
            $parser    = new TranslateParser(
                $container->getService('i18n'),
                $container->getTranslator(),
                $container->getPageProvider(),
                $replacer
            );

            $replacer->registerParser($parser);
        }
    )
);
