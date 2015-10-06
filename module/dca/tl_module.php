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

use Bit3\Contao\MetaPalettes\MetaPalettes;

/*
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['i18n_navigation'] =& $GLOBALS['TL_DCA']['tl_module']['palettes']['navigation'];
$GLOBALS['TL_DCA']['tl_module']['palettes']['i18n_form']       =& $GLOBALS['TL_DCA']['tl_module']['palettes']['form'];

MetaPalettes::appendFields('tl_module', 'login', 'redirect', ['jumpToI18n']);
MetaPalettes::appendFields('tl_module', 'logout', 'redirect', ['jumpToI18n']);
MetaPalettes::appendFields('tl_module', 'personalData', 'redirect', ['jumpToI18n']);
MetaPalettes::appendFields('tl_module', 'registration', 'redirect', ['jumpToI18n']);
MetaPalettes::appendFields('tl_module', 'lostPassword', 'redirect', ['jumpToI18n']);
MetaPalettes::appendFields('tl_module', 'closeAccount', 'redirect', ['jumpToI18n']);
MetaPalettes::appendFields('tl_module', 'search', 'redirect', ['jumpToI18n']);

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToI18n'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['jumpToI18n'],
    'inputType' => 'checkbox',
    'exclude'   => true,
    'eval'      => array(
        'tl_class'           => 'w50',
    ),
    'sql'       => "char(1) NOT NULL default ''"
);
