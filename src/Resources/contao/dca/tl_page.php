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

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'initializePalette'
];

$GLOBALS['TL_DCA']['tl_page']['fields']['i18n_disable'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['i18n_disable'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50'],
    'sql'       => "char(1) NOT NULL default ''",
];
