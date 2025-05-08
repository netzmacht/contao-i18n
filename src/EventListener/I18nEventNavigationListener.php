<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Override;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;

/** @extends I18nNavigationListener<CalendarEventsModel> */
#[AsHook('changelanguageNavigation')]
final class I18nEventNavigationListener extends I18nNavigationListener
{
    #[Override]
    protected function findCurrent(): CalendarEventsModel|null
    {
        $alias = Input::get($this->getUrlKey(), false, true);

        if ($alias === '') {
            return null;
        }

        $page = $this->getMainPage();
        if ($page === null) {
            return null;
        }

        $calendars = CalendarModel::findBy('jumpTo', $page->id);
        if (! $calendars instanceof Collection) {
            return null;
        }

        // Fix Contao bug that returns a collection (see contao-changelanguage#71)
        $options = ['limit' => 1, 'return' => 'Model'];

        return CalendarEventsModel::findPublishedByParentAndIdOrAlias(
            $alias,
            $calendars->fetchEach('id'),
            $options,
        );
    }

    /** {@inheritDoc} */
    #[Override]
    protected function handleNavigation(ChangelanguageNavigationEvent $event, Model $current): void
    {
        $event->getUrlParameterBag()->setUrlAttribute($this->getUrlKey(), $current->alias ?: $current->id);
        $event->getNavigationItem()->setTitle($current->title);
        $event->getNavigationItem()->setPageTitle($current->pageTitle);
    }
}
