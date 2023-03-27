<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Model\Page;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

final class PublishedI18nRegularPagesQuery
{
    /**
     * Database connection.
     */
    private Connection $connection;

    /** @param Connection $connection Database connection. */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute the query.
     *
     * @param int $start The start time as timestamp.
     * @param int $pid   Optional a parent page id.
     */
    public function execute(int $start, int $pid = 0): Result
    {
        return $this->connection->createQueryBuilder()
            ->select('p.*')
            ->from('tl_page', 'p')
            ->join('p', 'tl_page', 'r', 'r.id=p.hofff_root_page_id')
            ->where('p.pid=:pid')
            ->andWhere('(p.start=\'\' OR p.start <=:start)')
            ->andWhere('(p.stop=\'\' OR p.stop > :stop)')
            ->andWhere('p.published=\'1\'')
            ->andWhere('(r.fallback = \'\' or r.languageRoot > 0)')
            ->setParameter('pid', $pid)
            ->setParameter('start', $start)
            ->setParameter('stop', $start + 60)
            ->executeQuery();
    }
}
