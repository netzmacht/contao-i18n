<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Override;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;

/** @extends I18nNavigationListener<FaqModel> */
#[AsHook('changelanguageNavigation')]
final class I18nFaqNavigationListener extends I18nNavigationListener
{
    #[Override]
    protected function findCurrent(): FaqModel|null
    {
        $alias = Input::get($this->getUrlKey(), false, true);

        if ($alias === '') {
            return null;
        }

        $page = $this->getMainPage();
        if ($page === null) {
            return null;
        }

        $categories = FaqCategoryModel::findBy('jumpTo', $page->id);
        if (! $categories instanceof Collection) {
            return null;
        }

        // Fix Contao bug that returns a collection (see contao-changelanguage#71)
        $options = ['limit' => 1, 'return' => 'Model'];

        return FaqModel::findPublishedByParentAndIdOrAlias($alias, $categories->fetchEach('id'), $options);
    }

    /** {@inheritDoc} */
    #[Override]
    protected function handleNavigation(ChangelanguageNavigationEvent $event, Model $current): void
    {
        $event->getUrlParameterBag()->setUrlAttribute($this->getUrlKey(), $current->alias ?: $current->id);
        $event->getNavigationItem()->setTitle($current->question);
        $event->getNavigationItem()->setPageTitle($current->pageTitle);
    }
}
