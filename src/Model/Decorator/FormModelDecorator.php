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

namespace Netzmacht\Contao\I18n\Model\Decorator;

use Contao\FormModel;
use Contao\PageModel;

/**
 * Class FormModelDecorator decorates the module model to inject a specific jumpToPage.
 *
 * @package Netzmacht\Contao\I18n\Model\Decorator
 */
class FormModelDecorator extends FormModel
{
    /**
     * The form model.
     * 
     * @var FormModel
     */
    private $model;

    /**
     * The jump to page.
     *
     * @var PageModel
     */
    private $jumpToPage;
    
    /**
     * Construct.
     */
    public function __construct(FormModel $model, PageModel $jumpToPage)
    {
        $this->model      = $model;
        $this->jumpToPage = $jumpToPage;
    }

    /**
     * @inheritDoc
     */
    public function __clone()
    {
        $this->model = clone $this->model;
    }

    /**
     * @inheritDoc
     */
    public function __set($strKey, $varValue)
    {
        $this->model->__set($strKey, $varValue);
    }

    /**
     * @inheritDoc
     */
    public function __get($strKey)
    {
        if ($strKey === 'jumpTo') {
            return $this->jumpToPage->id;
        }

        return $this->model->__get($strKey);
    }

    /**
     * @inheritDoc
     */
    public function __isset($strKey)
    {
        return $this->model->__isset($strKey);
    }

    /**
     * @inheritDoc
     */
    public function row()
    {
        return $this->model->row();
    }

    /**
     * @inheritDoc
     */
    public function isModified()
    {
        return $this->model->isModified();
    }

    /**
     * @inheritDoc
     */
    public function setRow(array $arrData)
    {
        return $this->model->setRow($arrData);
    }

    /**
     * @inheritDoc
     */
    public function mergeRow(array $arrData)
    {
        return $this->model->mergeRow($arrData);
    }

    /**
     * @inheritDoc
     */
    public function markModified($strKey)
    {
        $this->model->markModified($strKey);
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->model->current();
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        return $this->model->save();
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        return $this->model->delete();
    }

    /**
     * @inheritDoc
     */
    public function getRelated($strKey, array $arrOptions = array())
    {
        if ($strKey === 'jumpTo') {
            return $this->jumpToPage;
        }

        return $this->model->getRelated($strKey, $arrOptions);
    }

    /**
     * @inheritDoc
     */
    public function refresh()
    {
        $this->model->refresh();
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        $this->model->detach();
    }

    /**
     * @inheritDoc
     */
    public function preventSaving()
    {
        $this->model->preventSaving();
    }
}
