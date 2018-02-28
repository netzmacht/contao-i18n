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

namespace Netzmacht\Contao\I18n\Model\Page;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

/**
 * Class PublishedI18nRegularPages
 */
class PublishedI18nRegularPagesQuery
{
    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * PublishedI18nRegularPagesQuery constructor.
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
     * @param int $start The start time as timestamp.
     * @param int $pid   Optional a parent page id.
     *
     * @return Statement
     */
    public function execute(int $start, int $pid = 0): Statement
    {
        return $this->connection->createQueryBuilder()
            ->select('p.*')
            ->from('tl_page', 'p')
            ->join('p', 'tl_page', 'r', 'r.id=p.hofff_root_page_id')
            ->where('p.pid=:pid')
            ->andWhere('(p.start=\'\' OR p.start <=:start)')
            ->andWhere('(p.stop=\'\' OR p.stop > :stop)')
            ->andWhere('p.published=\'1\'')
            ->andWhere('r.fallback = \'\'')
            ->setParameter('pid', $pid)
            ->setParameter('start', $start)
            ->setParameter('stop', ($start + 60))
            ->execute();
    }
}
