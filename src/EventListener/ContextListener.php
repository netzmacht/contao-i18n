<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\ContentModel;
use Contao\Model;
use Contao\ModuleModel;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

final class ContextListener
{
    public function __construct(
        private readonly ContextStack $contextStack,
        private readonly RepositoryManager $repositoryManager
    ) {
    }

    /**
     * Enter a context when using the module element.
     *
     * @param Model $model   The element model.
     * @param bool  $visible Visible state.
     */
    public function onIsVisibleElement(Model $model, bool $visible): bool
    {
        if (! $visible) {
            return false;
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

        return true;
    }

    /**
     * Leave a context after generating a frontend module.
     *
     * @param ModuleModel $model  The frontend module model.
     * @param string      $buffer The generated module.
     */
    public function onGetFrontendModule(ModuleModel $model, string $buffer): string
    {
        $this->contextStack->leaveContext(FrontendModuleContext::fromModel($model));

        return $buffer;
    }
}
