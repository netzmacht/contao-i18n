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

namespace Netzmacht\Contao\I18n\Hybrid;

use Netzmacht\Contao\I18n\I18nTrait;
use Netzmacht\Contao\I18n\Model\Decorator\ModuleModelDecorator;

/**
 * The I18n form redirects to the base page if defined.
 *
 * @package Netzmacht\Contao\I18n\Hybrid
 */
class Form extends \Contao\Form
{
    use I18nTrait;

    /**
     * @inheritDoc
     */
    protected function compile()
    {
        if ($this->jumpTo) {
            $i18n = $this->getI18n();
            $page = $this->objModel->getRelated('jumpTo');

            $jumpTo = $i18n->getTranslatedPage($page);

            if ($jumpTo && $jumpTo !== $page) {
                $this->objModel = new ModuleModelDecorator($this->objModel, $jumpTo);
                $this->jumpTo   = $jumpTo->id;
            }
        }

        return parent::compile();
    }
}
