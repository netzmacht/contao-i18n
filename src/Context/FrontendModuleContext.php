<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

use Contao\ModuleModel;
use Override;

/** @psalm-immutable */
final class FrontendModuleContext implements Context
{
    /**
     * The module type.
     */
    private string $moduleType;

    /**
     * The module id.
     */
    private int $moduleId;

    public function __construct(string $moduleType, int $moduleId)
    {
        $this->moduleType = $moduleType;
        $this->moduleId   = $moduleId;
    }

    public static function ofType(string $moduleType): self
    {
        return new self($moduleType, 0);
    }

    #[Override]
    public function match(Context $context): bool
    {
        if (! $context instanceof self) {
            return false;
        }

        if ($this->moduleType !== $context->moduleType) {
            return false;
        }

        if ($this->moduleId === 0) {
            return true;
        }

        return $this->moduleId === $context->moduleId;
    }

    /**
     * Create the context from the module model.
     *
     * @param ModuleModel $module The module model.
     */
    public static function fromModel(ModuleModel $module): self
    {
        return new self($module->type, $module->id);
    }

    /**
     * Get module type.
     */
    public function getModuleType(): string
    {
        return $this->moduleType;
    }

    /**
     * Get module id.
     */
    public function getModuleId(): int
    {
        return $this->moduleId;
    }
}
