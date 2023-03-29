<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

/** @psalm-immutable */
interface Context
{
    /**
     * Compare two contexts.
     *
     * @param Context $context Context to compare with.
     */
    public function match(Context $context): bool;
}
