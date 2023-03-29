<?php

declare(strict_types=1);

use Netzmacht\Contao\I18n\Module\I18nCustomNavigation;
use Netzmacht\Contao\I18n\Module\I18nNavigation;
use Netzmacht\Contao\I18n\PageType\I18nRegular;

/*
 * Pages
 */

$GLOBALS['TL_PTY']['i18n_regular'] = I18nRegular::class;

/*
 * Modules
 */

$GLOBALS['FE_MOD']['i18n']['i18n_navigation'] = I18nNavigation::class;
$GLOBALS['FE_MOD']['i18n']['i18n_customnav']  = I18nCustomNavigation::class;
