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

namespace Netzmacht\Contao\I18n\Context;

use Contao\ModuleModel;

/**
 * Class FrontendModuleContext
 *
 * @package Netzmacht\Contao\I18n\Context
 */
class FrontendModuleContext implements Context
{
    /**
     * The module type.
     *
     * @var string
     */
    private $moduleType;

    /**
     * The module id.
     *
     * @var int
     */
    private $moduleId;

    /**
     * FrontendModuleContext constructor.
     *
     * @param string $moduleType
     * @param int    $moduleId
     */
    public function __construct(string $moduleType, int $moduleId)
    {
        $this->moduleType = $moduleType;
        $this->moduleId   = $moduleId;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Context $context, bool $strict = false): bool
    {
        if (!$context instanceof FrontendModuleContext) {
            return false;
        }
        
        if ($this->moduleType !== $context->moduleType) {
            return false;
        }

        if ($strict && $this->moduleId !== $context->moduleId) {
            return false;
        }

        return true;
    }

    /**
     * Create the context from the module model.
     *
     * @param ModuleModel $module The module model.
     *
     * @return static
     */
    public static function fromModel(ModuleModel $module)
    {
        return new static($module->type, (int) $module->id);
    }

    /**
     * Get module class.
     *
     * @return string
     */
    public function getModuleType(): string
    {
        return $this->moduleType;
    }

    /**
     * Get module id.
     *
     * @return int
     */
    public function getModuleId(): int
    {
        return $this->moduleId;
    }
}
