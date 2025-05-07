<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Page;

use Contao\Model;
use Netzmacht\Contao\Toolkit\Data\Model\Specification;
use Override;
use RuntimeException;

final class TranslatedPageSpecification implements Specification
{
    /**
     * @param int    $mainLanguage Page id of the page in the main language.
     * @param string $language     The current language.
     */
    public function __construct(private readonly int $mainLanguage, private readonly string $language)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException Method is not implemented yet.
     */
    #[Override]
    public function isSatisfiedBy(Model $model): bool
    {
        throw new RuntimeException('isSatisfiedBy not implemented yet.');
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function buildQuery(array &$columns, array &$values): void
    {
        $columns[] = '.languageMain = ?';
        $columns[] = '(SELECT count(id) FROM tl_page r WHERE r.id=.hofff_root_page_id AND r.language=?) > 0';
        $values[]  = $this->mainLanguage;
        $values[]  = $this->language;
    }
}
