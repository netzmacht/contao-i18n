Contao I18n toolkit
==================

[![Build Status](http://img.shields.io/travis/netzmacht/contao-i18n/master.svg?style=flat-square)](https://travis-ci.org/netzmacht/contao-i18n)
[![Version](http://img.shields.io/packagist/v/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![License](http://img.shields.io/packagist/l/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![Downloads](http://img.shields.io/packagist/dt/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![Contao Community Alliance coding standard](http://img.shields.io/badge/cca-coding_standard-red.svg?style=flat-square)](https://github.com/contao-community-alliance/coding-standard)

This extensions provides an flexible way for multilingual websites.

Install
-------

You can install this extension using Composer.

```
$ php composer.phar require netzmacht/contao-i18n:~1.0
```

Requirements
------------

 * >= PHP 5.4
 * Contao 3.5 
 * Usage of [terminal42/contao-changelanguage](https://github.com/terminal42/contao-changelanguage)
 
Features
--------

### Language aware redirects

Module and form redirects are able to redirect to the translated version of the defined jump to page. So you only have 
to define the module once if only the target differs.

 1. Setup the pages and connect pages as usual with changelanguage.
 2. Set the redirect page to the fallback language.
 3. In a module set the `jumpToI18n` checkbox, for forms choose the `I18n form` content element/module to enable the 
    language aware redirect.

So you only have to define once the language relations! And you keep control if language redirects is required.

*Known limititation:* The language related redirect of modules does not work when using the Contao frontend preview. 

### Same pages in different languages

Sometimes you have to translate your website but some pages stays identical. If you use different page aliases in your 
language tree the fallback page is not loaded. 

The `I18n Regular page` is designed to solve this issue. It simply load all the content of the fallback page. So it's 
possible to have language aware aliases but use same content without duplication. 

On top of it the language is kept. This means that Contao uses the language defined in the page settings for all 
translation string.
 
### Translation insert tags

The two features above allow you to reduce the stupid duplication of modules or page when where is no difference. But 
sometimes some labels has to be translated as well. 

Use the `{{translate::*}}` insert tag for this or even it's shortcut `{{t::*}}`.

Following syntax is supported:

`{{t::path.to.translation}}`
Try to get the translation from the page_ALIAS domain. Fallback to website domain if not translated. If the page
type is an i18n page type, the alias of the base page is used instead.

In other words, you have to create a `page_ALIAS.php` translation file. If you have website wide translations use the 
`website.php` language file.

If no page alias is given, the page id is used instead. Folder aliases get escaped to underscores.

`{{t::domain:path.to.translation}}`
Translate from a given domain.

Note: The dot syntax is used for the array structure of the language file.

### Navigation modules

At the moment only the normal navigation module supports an i18n root page setting. Use the `I18n navigation` module for
this. 

Instead of using the defined root page it tries to find the correct related language page of it and then render the 
navigation.

### Language editor

If you start using the `translate` insert tag you probably want to provide an easy way to translate that labels in the 
backend. Have a look at [netzmacht/contao-language-editor](https://github.com/netzmacht/contao-language-editor) for it.

ATM it's still required to create the english language files so that the language editor knows this labels exists.
