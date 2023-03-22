<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

final class TranslatePageUrlContext implements Context
{
    public function match(Context $context, bool $strict = false): bool
    {
        return $context instanceof self;
    }
}
