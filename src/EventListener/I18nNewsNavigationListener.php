<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Override;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;

/** @extends I18nNavigationListener<NewsModel> */
#[AsHook('changelanguageNavigation')]
final class I18nNewsNavigationListener extends I18nNavigationListener
{
    /** {@inheritDoc} */
    #[Override]
    protected function findCurrent(): NewsModel|null
    {
        $alias = Input::get($this->getUrlKey(), false, true);

        if ($alias === '') {
            return null;
        }

        $page = $this->getMainPage();
        if ($page === null) {
            return null;
        }

        $archives = NewsArchiveModel::findBy('jumpTo', $page->id);
        if (! $archives instanceof Collection) {
            return null;
        }

        // Fix Contao bug that returns a collection (see contao-changelanguage#71)
        $options = ['limit' => 1, 'return' => 'Model'];

        return NewsModel::findPublishedByParentAndIdOrAlias($alias, $archives->fetchEach('id'), $options);
    }

    /** {@inheritDoc} */
    #[Override]
    protected function handleNavigation(ChangelanguageNavigationEvent $event, Model $current): void
    {
        $event->getUrlParameterBag()->setUrlAttribute($this->getUrlKey(), $current->alias ?: $current->id);
        $event->getNavigationItem()->setTitle($current->headline);
        $event->getNavigationItem()->setPageTitle($current->pageTitle);
    }
}
