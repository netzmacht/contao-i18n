<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

use Override;

/** @psalm-immutable */
final class TranslatePageUrlContext implements Context
{
    #[Override]
    public function match(Context $context): bool
    {
        return $context instanceof self;
    }
}
