<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Context;

use Override;

/** @psalm-immutable */
final class LocaleContext implements Context
{
    public function __construct(public readonly string $locale)
    {
    }

    public static function ofLocale(string $locale): self
    {
        return new self($locale);
    }

    #[Override]
    public function match(Context $context): bool
    {
        if (! $context instanceof self) {
            return false;
        }

        return $this->locale === $context->locale;
    }
}
