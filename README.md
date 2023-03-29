Contao I18n toolkit
==================

[![Build Status](https://img.shields.io/github/actions/workflow/status/netzmacht/contao-i18n/diagnostics.yml?style=flat-square&branch=master)](https://github.com/netzmacht/contao-i18n/actions/workflows/diagnostics.yml)
[![Version](http://img.shields.io/packagist/v/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![License](http://img.shields.io/packagist/l/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)
[![Downloads](http://img.shields.io/packagist/dt/netzmacht/contao-i18n.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-i18n)

This extension provides a flexible way for multilingual websites where parts of the main language should be reused on
the translated pages.

Install
-------

You can install this extension using Composer.

```
$ php composer.phar require netzmacht/contao-i18n:^3.0
```

Requirements
------------

 * PHP `^8.1`
 * Contao `4.12`
 * Usage of [terminal42/contao-changelanguage](https://github.com/terminal42/contao-changelanguage)
 
Features
--------

### Language aware redirects

The main idea of the repository is you want to reuse modules or content from the main page in the translations. If you 
do so - most links or redirects would go back to the main language.

That's where this extension hook into. It detects references to pages in another language tree. If the it's the case it
try to find the translation and manipulates the target page to the translation.

It works for all page references. No matter if you have a jumpTo in your form or module. But you stay in control. You can 
disable this behaviour by disable it in the page of the main language. Also the change language module is blacklisted, so
you are still able to link to another language.

 1. Setup the pages and connect pages as usual with changelanguage.
 2. Set the redirect page to the fallback language.
 3. In you want to disable the *"page translation"* you may configure it in your page setting.

So you only have to define once the language relations! And you keep control if language redirects is required.

### Same pages in different languages

Sometimes you have to translate your website but some pages stays identical. If you use different page aliases in your 
language tree the fallback page is not loaded. 

The `I18n Regular page` is designed to solve this issue. It simply loads all the content of the fallback page. So it's 
possible to have language aware aliases but use same content without duplication. 

On top of it the language is kept. This means that Contao uses the language defined in the page settings. This enables
 you to use insert tags to translate contents.
 
### Same news, events and faqs in different languages

If you want to reuse news, events and faqs in different languages, it's supported. You only have to use the
`I18n Regular page` as the reader page in the translation tree. This extension automatically creates the search page 
entries and sitemap entries for you.

**Known limitation**: The rss feed are not translated at the moment.  
 
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
Translate from a custom domain.

Note: The dot syntax is used for the array structure of the language file.
Note: Nested insert tags are supported in the translation strings.

### Navigation modules

This extension provides two navigation modules which goes a step further. They does not only replace the url but the 
whole page. So you get an translated navigation module even if you have a reference page set in a navigation module or 
you use an individual navigation.


Configuration
-------------

By default, contao-i18n does not delete any articles in the contao i18n regular page. If they are not connected as 
page to override other pages, they are simply ignored. However, you can enable the cleanup. If cleanup is enabled all
articles are deleted which are not configured as an article override.

```yaml
# app/config/config.yml

netzmacht_contao_i18n:
    article_cleanup: true
```

Development
-----------

This repository contains a [ddev](https://github.com/ddev/ddev) configuration for a local test and development 
environment of this extension. It also provides a snapshot of the database. So it's easy to set up a local instance for 
yourself. For the general usage of ddev read the [ddev documentation](https://ddev.readthedocs.io).

Following credentials are used:

**Backend user**

 - Username: `admin`
 - Password: `adminuser`

**Install tool**

 - Password: `adminuser`
