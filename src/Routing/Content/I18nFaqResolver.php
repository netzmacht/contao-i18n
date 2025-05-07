<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Routing\Content;

use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Override;

final class I18nFaqResolver extends I18nResolver
{
    #[Override]
    public function resolve(object $content): ContentUrlResult|null
    {
        if (! $content instanceof FaqModel) {
            return null;
        }

        $categoryAdapter = $this->framework->getAdapter(FaqCategoryModel::class);

        /** @psalm-suppress UndefinedPropertyFetch */
        return $this->translateResolve((int) $categoryAdapter->findById($content->pid)?->jumpTo);
    }
}
