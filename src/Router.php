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

namespace Netzmacht\Contao\I18n;

use Contao\Model;
use Contao\ModuleModel;

/**
 * Router modifies the jumpTo informations of modules.
 *
 * @package Netzmacht\Contao\I18n
 */
class Router
{
    /**
     * Supported types.
     *
     * @var array
     */
    private $supportedTypes;

    /**
     * I18n.
     *
     * @var I18n
     */
    private $i18n;

    /**
     * Router constructor.
     *
     * @param I18n  $i18n           I18n service.
     * @param array $supportedTypes Supported types.
     */
    public function __construct(I18n $i18n, array $supportedTypes)
    {
        $this->supportedTypes = $supportedTypes;
        $this->i18n           = $i18n;
    }

    /**
     * Get the router instance.
     *
     * @return static
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getInstance()
    {
        return $GLOBALS['container']['i18n.router'];
    }

    /**
     * Listen on the isVisibleElement hook to change the routing information.
     *
     * @param Model $model   Element model.
     * @param bool  $visible Visible state.
     *
     * @return bool
     */
    public function onIsVisibleElement(Model $model, $visible)
    {
        if ($this->supports($model)) {
            $this->setRouting($model);
        }

        return $visible;
    }

    /**
     * Check if element a supported for i18n redirects.
     *
     * @param Model $model Given element model.
     *
     * @return bool
     */
    public function supports(Model $model)
    {
        if ($model instanceof ModuleModel
            && isset($this->supportedTypes['modules'][$model->type])
            && $model->jumpToI18n
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set the routing information.
     *
     * @param ModuleModel $model Module model.
     *
     * @return void
     */
    private function setRouting(ModuleModel $model)
    {
        $config = (array) $this->supportedTypes['modules'][$model->type];

        foreach ($config as $field) {
            $pageId = $model->$field;
            $page   = $this->i18n->getTranslatedPage($pageId);

            if ($page && $page->id != $pageId) {
                // Detach model so that dynamically changed value does not get stored in db.
                $model->detach();

                $model->$field = $page->id;
            }
        }
    }
}
