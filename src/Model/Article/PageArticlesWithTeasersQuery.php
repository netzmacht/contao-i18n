<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    contao-18n
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later https://github.com/netzmacht/contao-i18n/blob/master/LICENSE
 * @filesource
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Article;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

/**
 * Class ArticleWithTeasersQuery
 */
final class PageArticlesWithTeasersQuery
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * PageArticlesWithTeasersQuery constructor.
     *
     * @param Connection $connection Database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute the query.
     *
     * @param int $pageId The page id of the articles.
     * @param int $start  Start time.
     *
     * @return Statement
     */
    public function execute(int $pageId, int $start)
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_article')
            ->where('pid=:pid')
            ->andWhere('(start=\'\' OR start <=:start)')
            ->andWhere('(stop=\'\' OR stop > :stop)')
            ->andWhere('published=\'1\'')
            ->andWhere('showTeaser=\'1\'')
            ->setParameter('pid', $pageId)
            ->setParameter('start', $start)
            ->setParameter('stop', ($start + 60))
            ->orderBy('sorting')
            ->execute();
    }
}
