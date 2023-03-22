<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

use function array_slice;
use function count;

final class ContextStack
{
    /**
     * Context stack.
     *
     * @var Context[]|array
     */
    private array $contexts = [];

    /**
     * Enter a new context.
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
     */
    public function matchCurrentContext(Context $context, bool $strict = false): bool
    {
        if (empty($this->contexts)) {
            return false;
        }

        $index   = count($this->contexts) - 1;
        $current = $this->contexts[$index];

        return $current->match($context, $strict);
    }

    /**
     * Leave a context.
     *
     * If the context is not the last one, all contexts entered after the given one will be left.
     *
     * @param Context $context The context.
     */
    public function leaveContext(Context $context): void
    {
        foreach ($this->contexts as $index => $value) {
            if (! $value->match($context, true)) {
                continue;
            }

            $this->contexts = array_slice($this->contexts, 0, $index);
            break;
        }
    }
}
