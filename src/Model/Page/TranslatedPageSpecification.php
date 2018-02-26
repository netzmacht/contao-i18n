<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Page;

use Contao\Model;
use Netzmacht\Contao\Toolkit\Data\Model\Specification;

/**
 * Class TranslatedPageSpecification
 *
 * @package Netzmacht\Contao\I18n\Model\Page
 */
class TranslatedPageSpecification implements Specification
{
    /**
     * The language.
     *
     * @var string
     */
    private $language;

    /**
     * Page id of the page in the main language.
     *
     * @var int
     */
    private $mainLanguage;

    /**
     * TranslatedPageSpecification constructor.
     *
     * @param string $language     The current language.
     * @param int    $mainLanguage Page id of the page in the main language.
     */
    public function __construct(string $language, int $mainLanguage)
    {
        $this->language     = $language;
        $this->mainLanguage = $mainLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(Model $model)
    {
        throw new \RuntimeException('isSatisfiedBy not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function buildQuery(array &$columns, array &$values)
    {
        $columns[] = ['.languageMain = ?'];
        $columns[] = ['(SELECT count(id) FROM tl_page r WHERE r.id=.cca_rr_root AND r.language=?)'];
        $values[]  = $this->mainLanguage;
        $values[]  = $this->language;
    }
}
