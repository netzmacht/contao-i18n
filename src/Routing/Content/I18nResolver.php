<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Routing\Content;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Override;

abstract class I18nResolver implements ContentUrlResolverInterface
{
    public function __construct(
        protected readonly I18nPageRepository $i18nPageRepository,
        protected readonly ContaoFramework $framework,
        private readonly ContentUrlResolverInterface $originalResolver,
    ) {
    }

    protected function translateResolve(int $redirectId): ContentUrlResult|null
    {
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        /** @psalm-suppress UndefinedPropertyFetch */
        $redirectPage = $pageAdapter->findPublishedById($redirectId);
        if ($redirectPage !== null) {
            $translatedPage = $this->i18nPageRepository->getTranslatedPage($redirectPage);

            return ContentUrlResult::resolve($translatedPage);
        }

        // Link to the default page
        return ContentUrlResult::resolve($redirectPage);
    }

    protected function translateRedirect(int $redirectId): ContentUrlResult|null
    {
        $pageAdapter  = $this->framework->getAdapter(PageModel::class);
        $redirectPage = $pageAdapter->findPublishedById($redirectId);

        if ($redirectPage !== null) {
            $translatedPage = $this->i18nPageRepository->getTranslatedPage($redirectPage);

            return ContentUrlResult::redirect($translatedPage ?? $redirectPage);
        }

        return null;
    }

    /** {@inheritDoc} */
    #[Override]
    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        $translatedPage = $this->i18nPageRepository->getTranslatedPage($pageModel);

        return $this->originalResolver->getParametersForContent($content, $translatedPage ?? $pageModel);
    }
}
