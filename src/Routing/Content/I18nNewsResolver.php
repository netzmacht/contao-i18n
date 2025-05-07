<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Routing\Content;

use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Override;

final class I18nNewsResolver extends I18nResolver
{
    #[Override]
    public function resolve(object $content): ContentUrlResult|null
    {
        if (! $content instanceof NewsModel) {
            return null;
        }

        switch ($content->source) {
            // Link to an external page
            case 'external':
            // Link to an article
            case 'article':
                return null;

            // Link to an internal page
            case 'internal':
                return $this->translateRedirect($content->jumpTo);
        }

        $archiveAdapter = $this->framework->getAdapter(NewsArchiveModel::class);

        /** @psalm-suppress UndefinedPropertyFetch */
        return $this->translateResolve((int) $archiveAdapter->findById($content->pid)?->jumpTo);
    }
}
