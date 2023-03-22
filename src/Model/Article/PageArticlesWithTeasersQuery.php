<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Article;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

final class PageArticlesWithTeasersQuery
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute the query.
     *
     * @param int $pageId The page id of the articles.
     * @param int $start  Start time.
     */
    public function execute(int $pageId, int $start): Result
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
            ->setParameter('stop', $start + 60)
            ->orderBy('sorting')
            ->executeQuery();
    }
}
