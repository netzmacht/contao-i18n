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

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Model;
use Contao\ModuleModel;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\I18n\I18n;

/**
 * Class ContextListener updates the context when rendering a page.
 */
class ContextListener
{
    /**
     * Contao i18n.
     *
     * @var I18n
     */
    private $i18n;

    /**
     * ContextListener constructor.
     *
     * @param I18n $i18n
     */
    public function __construct(I18n $i18n)
    {
        $this->i18n = $i18n;
    }

    /**
     * Enter a context when using the module element.
     *
     * @param Model $model   The element model.
     * @param bool  $visible Visible state.
     *
     * @return bool
     */
    public function onIsVisibleElement(Model $model, bool $visible): bool
    {
        if (!$visible) {
            return $visible;
        }

        if ($model instanceof ModuleModel) {
            $this->i18n->enterContext(FrontendModuleContext::fromModel($model));
        }

        // TODO: Handle module include elements

        return $visible;
    }

    /**
     * Leave a context after generating a frontend module.
     *
     * @param ModuleModel $model  The frontend module model.
     * @param string      $buffer The generated module.
     *
     * @return string
     */
    public function onGetFrontendModule(ModuleModel $model, string $buffer): string
    {
        $this->i18n->leaveContext(FrontendModuleContext::fromModel($model));

        return $buffer;
    }
}
