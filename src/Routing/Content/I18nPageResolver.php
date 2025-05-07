<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Routing\Content;

use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\CoreBundle\Routing\Content\PageResolver;
use Contao\PageModel;
use Netzmacht\Contao\I18n\Context\ContextStack;
use Netzmacht\Contao\I18n\Context\FrontendModuleContext;
use Netzmacht\Contao\I18n\Model\Page\I18nPageRepository;
use Override;

final class I18nPageResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContextStack $contextStack,
        private readonly I18nPageRepository $i18nPageRepository,
        private readonly PageResolver $pageResolver,
    ) {
    }

    #[Override]
    public function resolve(object $content): ContentUrlResult|null
    {
        if (! $content instanceof PageModel) {
             return null;
        }

        if ($this->contextStack->matchCurrentContext(FrontendModuleContext::ofType('changelanguage'))) {
            return null;
        }

        $translatedPage = $this->i18nPageRepository->getTranslatedPage($content, $this->contextStack->locale());
        if ($translatedPage && $translatedPage->id !== $content->id) {
            return ContentUrlResult::resolve($translatedPage);
        }

        return $this->pageResolver->resolve($content);
    }

    /** {@inheritDoc} */
    #[Override]
    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        return $this->pageResolver->getParametersForContent(
            $content,
            $this->i18nPageRepository->getTranslatedPage($pageModel) ?? $pageModel,
        );
    }
}
