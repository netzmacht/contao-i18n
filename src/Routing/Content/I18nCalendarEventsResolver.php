<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Routing\Content;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Override;

final class I18nCalendarEventsResolver extends I18nResolver
{
    #[Override]
    public function resolve(object $content): ContentUrlResult|null
    {
        if (! $content instanceof CalendarEventsModel) {
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

        $calendarAdapter = $this->framework->getAdapter(CalendarModel::class);

        /** @psalm-suppress UndefinedPropertyFetch */
        return $this->translateResolve((int) $calendarAdapter->findById($content->pid)?->jumpTo);
    }
}
