<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

interface Context
{
    /**
     * Compare two contexts.
     *
     * @param Context $context Check context to compare with.
     * @param bool    $strict  Some contexts may provide a strict mode to compare.
     */
    public function match(Context $context, bool $strict = false): bool;
}
