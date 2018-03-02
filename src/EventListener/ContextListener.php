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

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\ContentModel;
use Contao\Model;
use Contao\ModuleModel;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

/**
 * Class ContextListener updates the context when rendering a page.
 */
class ContextListener
{
    /**
     * Context stack.
     *
     * @var ContextStack
     */
    private $contextStack;

    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * ContextListener constructor.
     *
     * @param ContextStack      $contextStack      Context stack.
     * @param RepositoryManager $repositoryManager Repository manager.
     */
    public function __construct(ContextStack $contextStack, RepositoryManager $repositoryManager)
    {
        $this->contextStack      = $contextStack;
        $this->repositoryManager = $repositoryManager;
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
            $this->contextStack->enterContext(FrontendModuleContext::fromModel($model));
        }

        // The isVisibleElement hook is not triggered for the module if it's included using a content element.
        // So we have to track it manually.
        if ($model instanceof ContentModel && $model->type === 'module') {
            $module = $this->repositoryManager->getRepository(ModuleModel::class)->find((int) $model->module);
            if ($module) {
                $this->contextStack->enterContext(FrontendModuleContext::fromModel($module));
            }
        }
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
        $this->contextStack->leaveContext(FrontendModuleContext::fromModel($model));

        return $buffer;
    }
}
