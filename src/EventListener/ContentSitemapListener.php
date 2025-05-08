<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Model;
use Contao\PageModel;
use Generator;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\LocaleContext;

use function in_array;

/** @template TParent of Model */
abstract class ContentSitemapListener
{
    public function __construct(
        protected readonly ContextStack $contextStack,
        protected readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(SitemapEvent $event): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        foreach ($this->collectPages($event->getRootPageIds()) as $url) {
            $event->addUrlToDefaultUrlSet($url);
        }
    }

    /**
     * Collect all page urls for the given root ids.
     *
     * @param list<int> $rootIds The root page ids
     *
     * @return Generator<string>
     */
    abstract protected function collectPages(array $rootIds): Generator;

    /**
     * @param array<string, PageModel>     $translations
     * @param array<int, array<int, bool>> $processed
     * @param TParent                      $parent
     * @param list<int>                    $rootIds
     *
     * @return Generator<string>
     */
    protected function progressTranslations(
        array $translations,
        array &$processed,
        int $time,
        Model $parent,
        array $rootIds,
    ): Generator {
        foreach ($translations as $language => $translation) {
            $context = LocaleContext::ofLocale($language);
            $this->contextStack->enterContext($context);
            $this->urlGenerator->reset();

            // Skip FAQs outside the root nodes
            if (
                (! empty($rootIds) && ! in_array($translation->hofff_root_page_id, $rootIds))
                || $translation->type !== 'i18n_regular'
            ) {
                $this->contextStack->leaveContext($context);
                continue;
            }

            foreach ($this->processTranslation($parent, $translation, $processed, $time) as $url) {
                yield $url;
            }

            $this->contextStack->leaveContext($context);
        }

        $this->urlGenerator->reset();
    }

    /**
     * @param TParent                      $parent
     * @param array<int, array<int, bool>> $processed
     *
     * @return Generator<string>
     */
    abstract protected function processTranslation(
        Model $parent,
        PageModel $translation,
        array &$processed,
        int $time,
    ): Generator;

    /**
     * Check if a page is published.
     *
     * @param PageModel $pageModel The page model.
     * @param int       $time      The current time.
     */
    protected function isPagePublished(PageModel $pageModel, int $time): bool
    {
        if (! $pageModel->published) {
            return false;
        }

        if ($pageModel->start !== '' && $pageModel->start > $time) {
            return false;
        }

        return $pageModel->stop === '' || $pageModel->stop > $time + 60;
    }

    /**
     * Check if a page should be added to the sitemap. If not being in sitemap mode, always true is returned.
     *
     * @param PageModel $pageModel Page model.
     */
    protected function shouldPageBeAddedToSitemap(PageModel $pageModel): bool
    {
        // The target page is protected (see #8416)
        if ($pageModel->protected) {
            return false;
        }

        // The target page is exempt from the sitemap (see #6418)
        return $pageModel->sitemap !== 'map_never';
    }

    /** @param array<int,array<int,bool>> $processed Cache of processed paged. */
    protected function shouldBeProcessed(array &$processed, int $time, PageModel $translation, int $category): bool
    {
        // Get the URL of the jumpTo page
        if (! isset($processed[$category][$translation->id])) {
            $processed[$category][(int) $translation->id] = false;

            // The target page has not been published (see #5520)
            if (! $this->isPagePublished($translation, $time)) {
                return false;
            }

            if (! $this->shouldPageBeAddedToSitemap($translation)) {
                return false;
            }

            // Generate the URL
            $processed[$category][(int) $translation->id] = true;
        }

        return $processed[$category][$translation->id];
    }
}
