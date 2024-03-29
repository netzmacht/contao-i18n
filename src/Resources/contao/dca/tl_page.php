<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'initializePalette',
];

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'initializePageTypeOptionsCallback',
];

$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'createI18nArticles',
];


/*
 * Operations
 */

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['articles']['button_callback'] = [
    'netzmacht.contao_i18n.listeners.dca.page',
    'editArticles',
];

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
        'buttons' => ['copy' => false, 'up' => false, 'down' => false],
    ],
    'load_callback' => [
        ['netzmacht.contao_i18n.listeners.dca.page', 'loadI18nArticles'],
    ],
    'sql'       => 'blob NULL',
];
