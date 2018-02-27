<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

/*
 * Pages
 */
$GLOBALS['TL_PTY']['i18n_regular'] = 'Netzmacht\Contao\I18n\PageType\I18nRegular';

/*
 * Modules
 */
$GLOBALS['FE_MOD']['i18n']['i18n_navigation'] = 'Netzmacht\Contao\I18n\Module\I18nNavigation';
$GLOBALS['FE_MOD']['i18n']['i18n_customnav']  = 'Netzmacht\Contao\I18n\Module\I18nCustomNavigation';
