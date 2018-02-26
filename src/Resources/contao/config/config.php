<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *

/*
 * Pages
 */
$GLOBALS['TL_PTY']['i18n_regular'] = 'Netzmacht\Contao\I18n\PageType\I18nRegular';

/*
 * Modules
 */
$GLOBALS['FE_MOD']['i18n']['i18n_navigation'] = 'Netzmacht\Contao\I18n\Module\Navigation';

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Netzmacht\Contao\I18n\SearchIndex\SitemapBuilder', 'build');

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
