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

namespace Netzmacht\Contao\I18n\Context;

/**
 * Class ContextStack
 */
final class ContextStack
{
    /**
     * Context stack.
     *
     * @var Context[]|array
     */
    private $contexts = [];

    /**
     * Enter a new context.
     *
     * @param Context $context The context.
     *
     * @return void
     */
    public function enterContext(Context $context): void
    {
        $this->contexts[] = $context;
    }

    /**
     * Match a given context to a current context.
     *
     * If no current context is in the stack, false is returned.
     *
     * @param Context $context The context.
     * @param bool    $strict  Strict mode. Some contexts supports strict mode comparison.
     *
     * @return bool
     */
    public function matchCurrentContext(Context $context, bool $strict = false): bool
    {
        if (empty ($this->contexts)) {
            return false;
        }

        $index   = (count($this->contexts) - 1);
        $current = $this->contexts[$index];

        return $current->match($context, $strict);
    }

    /**
     * Leave a context.
     *
     * If the context is not the last one, all contexts entered after the given one will be left.
     *
     * @param Context $context The context.
     *
     * @return void
     */
    public function leaveContext(Context $context): void
    {
        foreach ($this->contexts as $index => $value) {
            if ($value->match($context, true)) {
                $this->contexts = array_slice($this->contexts, 0, $index);
                break;
            }
        }
    }
}
