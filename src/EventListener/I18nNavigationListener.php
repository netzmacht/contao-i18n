<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Model;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Netzmacht\Contao\I18n\PageProvider\PageProvider;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;

/** @template TModel of Model */
abstract class I18nNavigationListener
{
    public function __construct(
        protected readonly I18nPageRepository $i18nPageRepository,
        private readonly PageProvider $pageProvider,
    ) {
    }

    public function __invoke(ChangelanguageNavigationEvent $event): void
    {
        $current = $this->findCurrent();
        if ($current === null) {
            return;
        }

        $navigationItem = $event->getNavigationItem();

        if ($navigationItem->isCurrentPage()) {
            $this->handleNavigation($event, $current);

            return;
        }

        // Remove the news/event/faq alias from the URL if there is no actual
        // reader page assigned
        if (! $navigationItem->isDirectFallback()) {
            $event->getUrlParameterBag()->removeUrlAttribute($this->getUrlKey());
        }

        $page = $navigationItem->getTargetPage();
        if ($page === null) {
            return;
        }

        if ($this->i18nPageRepository->isI18nPage($page->type)) {
            $this->handleNavigation($event, $current);

            return;
        }

        $translated = $this->i18nPageRepository->getTranslatedPage($page);
        if ($translated === null) {
            return;
        }

        $this->handleNavigation($event, $current);
    }

    protected function getMainPage(): PageModel|null
    {
        $page = $this->pageProvider->getPage();
        if ($page === null) {
            return null;
        }

        if ($this->i18nPageRepository->isI18nPage($page->type)) {
            return $this->i18nPageRepository->getMainPage($page);
        }

        return $page;
    }

    protected function getUrlKey(): string
    {
        return 'auto_item';
    }

    /** @param TModel $current */
    abstract protected function handleNavigation(ChangelanguageNavigationEvent $event, Model $current): void;

    /** @return TModel|null */
    abstract protected function findCurrent(): Model|null;
}
