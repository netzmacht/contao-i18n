<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2016 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\I18n\Element;

/**
 * Class ContentModule.
 *
 * @package Netzmacht\Contao\I18n\Element
 */
class ContentModule extends \ContentModule
{
    /**
     * {@inheritDoc}
     */
    public function generate()
    {
        $objModule = \ModuleModel::findByPk($this->module);

        if ($objModule === null || !static::isVisibleElement($objModule)) {
            return '';
        }

        $strClass = \Module::findClass($objModule->type);
        if (!class_exists($strClass)) {
            return '';
        }
        
        $objModule->typePrefix = 'ce_';
        
        /** @var \Module $objModule */
        $objModule = new $strClass($objModule, $this->strColumn);
        
        // Overwrite spacing and CSS ID
        $objModule->origSpace = $objModule->space;
        $objModule->space     = $this->space;
        $objModule->origCssID = $objModule->cssID;
        $objModule->cssID     = $this->cssID;

        return $objModule->generate();
    }
}
