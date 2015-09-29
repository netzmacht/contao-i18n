Contao I18n toolkit
==================

[![Build Status](http://img.shields.io/travis/netzmacht/contao-i18n/master.svg?style=flat-square)](https://travis-ci.org/netzmacht/contao-i18n)
[![Version](http://img.shields.io/packagist/v/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![License](http://img.shields.io/packagist/l/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![Downloads](http://img.shields.io/packagist/dt/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![Contao Community Alliance coding standard](http://img.shields.io/badge/cca-coding_standard-red.svg?style=flat-square)](https://github.com/contao-community-alliance/coding-standard)

This extensions provides an flexible way for multilingual websites.

The main goal is to allow to manage all the contents in the fallback page instead of duplicating all the contents. To
 achieve the goal it provides
  
 * An translation insert tag so that the content can easily translated.
 * Provides an new page type `i18n_regular` which loads the content of the fallback page.
 * Provides several modules where the `jumpTo` page get translated. This means instead of getting the fallback page
   the connected page in the current content is loaded.
   At the moment `form` and `navigation` is supported. More will probably follow
   
You still have to define the separate site structure. This way you can easily translate the site specific settings. And 
you keep the flexibility to use custom content on the translated pages.

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


Insert Tag syntax
-----------------

To translate the content an insert tag is provided. It is triggered with `{{translate::}}` or even shorter `{{t:}}`.

Following syntax is supported:

`{{t::path.to.translation}}`
Try to get the translation from the page_ALIAS domain. Fallback to website domain if not translated. If the page
type is an i18n page type, the alias of the base page is used instead.

If no page alias is given, the page id is used instead. Folder aliases get escaped to underscores.

`{{t::domain:path.to.translation}}`
Translate from a given domain.

Note: The dot syntax is used for the array structure of the language file.
