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

/*
 * Config
 */

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'initializePalette'
];

$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'createI18nArticles'
];


/*
 * Operations
 */

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['articles']['button_callback'] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'editArticles'
];


/*
 * Palettes
 */

$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][]           = 'i18n_article_override';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['i18n_article_override'] = 'i18n_articles';


/*
 * Fields
 */

$GLOBALS['TL_DCA']['tl_page']['fields']['i18n_disable'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['i18n_disable'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['i18n_article_override'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['i18n_article_override'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50', 'submitOnChange' => true],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['i18n_articles'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['i18n_articles'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'tl_class'     => 'clr long',
        'columnFields' => [
            'article' => [
                'label'            => &$GLOBALS['TL_LANG']['tl_page']['i18n_articles_article'],
                'exclude'          => true,
                'inputType'        => 'select',
                'options_callback' => ['netzmacht.contao_i18n.listeners.dca.page', 'getBasePageArticlesOptions'],
                'eval'             => ['style' => 'width:100%', 'chosen' => true, 'includeBlankOption' => true],
            ],
            'mode'    => [
                'label'     => &$GLOBALS['TL_LANG']['tl_page']['i18n_articles_mode'],
                'exclude'   => true,
                'inputType' => 'select',
                'options'   => ['exclude', 'override'],
                'reference' => &$GLOBALS['TL_LANG']['tl_page']['i18n_articles_modes'],
                'eval'      => ['style' => 'width:100%', 'chosen' => true],
            ],
        ],
    ],
    'load_callback' => [
        ['netzmacht.contao_i18n.listeners.dca.page', 'loadI18nArticles'],
    ],
    'sql'       => 'blob NULL',
];
