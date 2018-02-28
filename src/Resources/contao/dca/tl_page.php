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
