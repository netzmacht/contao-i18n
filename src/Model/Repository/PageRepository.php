<?php

/**
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\I18n\Model\Repository;

use Contao\Database;
use Contao\Model\Registry;
use Contao\PageModel;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

/**
 * PageRepository used in the i18n context.
 *
 * @package Netzmacht\Contao\I18n\Model\Repository
 */
class PageRepository extends ContaoRepository
{
    /**
     * Database connection.
     *
     * @var Database
     */
    private $database;

    /**
     * Model registry.
     *
     * @var Registry
     */
    private $modelRegistry;

    /**
     * PageRepository constructor.
     *
     * @param Database $database      Database.
     * @param Registry $modelRegistry Model registry.
     * @param string   $modelClass    The model class.
     */
    public function __construct(Database $database, Registry $modelRegistry, $modelClass)
    {
        parent::__construct($modelClass);

        $this->database      = $database;
        $this->modelRegistry = $modelRegistry;
    }

    /**
     * Find page by id or alias.
     *
     * @param int|string $pageIdOrAlias Page id or alias.
     *
     * @return PageModel|null
     */
    public function find($pageIdOrAlias)
    {
        if (is_numeric($pageIdOrAlias)) {
            return parent::find($pageIdOrAlias);
        }

        return $this->findBy(['alias = ?'], [$pageIdOrAlias], ['return' => 'Model']);
    }

    /**
     * Find a translated page.
     *
     * @param int    $pageId   Current page.
     * @param string $language Page language.
     *
     * @return PageModel|null
     */
    public function findTranslatedPage($pageId, $language)
    {
        $query = <<<SQL
SELECT p.* FROM tl_page p
JOIN tl_page r ON r.id = p.cca_rr_root AND r.language = ?
WHERE p.languageMain = ?
SQL;

        $result = $this->database->prepare($query)->limit(1)->execute($language, $pageId);
        if ($result->numRows < 1) {
            return null;
        }

        $page = $this->modelRegistry->fetch('tl_page', $result->id);
        if ($page) {
            return $page;
        }

        return new PageModel($result);
    }
}
