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

/*
 * Pages
 */
$GLOBALS['TL_PTY']['i18n_regular'] = 'Netzmacht\Contao\I18n\Page\Regular';

/*
 * Modules
 */
$GLOBALS['FE_MOD']['i18n']['i18n_navigation'] = 'Netzmacht\Contao\I18n\Module\Navigation';
$GLOBALS['FE_MOD']['i18n']['i18n_form']       = 'Netzmacht\Contao\I18n\Hybrid\Form';

/*
 * Content elements
 */
$GLOBALS['TL_CTE']['includes']['i18n_form'] = 'Netzmacht\Contao\I18n\Hybrid\Form';

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Netzmacht\Contao\I18n\InsertTag\Replacer', 'replace');
$GLOBALS['TL_HOOKS']['isVisibleElement'][]  = array('Netzmacht\Contao\I18n\Router', 'onIsVisibleElement');

/*
 * I18n routing
 */
$GLOBALS['I18N_ROUTING']['modules']['login']        = ['jumpTo'];
$GLOBALS['I18N_ROUTING']['modules']['logout']       = ['jumpTo'];
$GLOBALS['I18N_ROUTING']['modules']['personalData'] = ['jumpTo'];
$GLOBALS['I18N_ROUTING']['modules']['registration'] = ['jumpTo', 'reg_jumpTo'];
$GLOBALS['I18N_ROUTING']['modules']['lostPassword'] = ['jumpTo', 'reg_jumpTo'];
$GLOBALS['I18N_ROUTING']['modules']['closeAccount'] = ['jumpTo'];
$GLOBALS['I18N_ROUTING']['modules']['search']       = ['jumpTo'];
