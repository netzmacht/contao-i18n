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

namespace Netzmacht\Contao\I18n\Cleanup;

use Contao\ArticleModel;
use Contao\BackendUser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\DataContainer;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Query\QueryBuilder;
use Netzmacht\Contao\Toolkit\Callback\Invoker;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Dca\Definition;
use Netzmacht\Contao\Toolkit\Dca\Manager;

/**
 * Class I18nArticleCleaner
 */
final class I18nPageArticleCleaner
{
    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * Data container manager.
     *
     * @var Manager
     */
    private $dcaManager;

    /**
     * Backend user adapter.
     *
     * @var BackendUser|Adapter
     */
    private $backendUser;

    /**
     * Callback invoker.
     *
     * @var Invoker
     */
    private $callbackInvoker;

    /**
     * I18nArticleCleaner constructor.
     *
     * @param RepositoryManager   $repositoryManager Repository manager.
     * @param Manager             $dcaManager        Repository manager.
     * @param BackendUser|Adapter $backendUser       Backend user adapter.
     * @param Invoker             $callbackInvoker   Callback invoker.
     */
    public function __construct(
        RepositoryManager $repositoryManager,
        Manager $dcaManager,
        $backendUser,
        Invoker $callbackInvoker
    ) {
        $this->repositoryManager = $repositoryManager;
        $this->dcaManager        = $dcaManager;
        $this->backendUser       = $backendUser;
        $this->callbackInvoker   = $callbackInvoker;
    }

    /**
     * Clean up articles of the page.
     *
     * @param DataContainer $dataContainer Data container driver of tl_page.
     *
     * @return void
     *
     * @throws InvalidArgumentException When an invalid argument is passed to the delete statement.
     */
    public function cleanupUnrelatedArticles($dataContainer): void
    {
        $articleRepository = $this->repositoryManager->getRepository(ArticleModel::class);
        $collection        = $articleRepository->findBy(['.pid=?', '.languageMain=0'], [$dataContainer->id]);

        foreach (($collection ?? []) as $articleModel) {
            $this->deleteArticle($articleModel, $dataContainer);
        }
    }

    /**
     * Delete a article. The article is listed
     *
     * @param ArticleModel  $articleModel  Article model.
     * @param DataContainer $dataContainer Data container driver of tl_page.
     *
     * @return void
     *
     * @throws InvalidArgumentException When an invalid argument is passed to the delete statement.
     */
    public function deleteArticle(ArticleModel $articleModel, $dataContainer): void
    {
        $delete = [
            ArticleModel::getTable() => [$articleModel->id],
        ];

        $this->collectChildren(ArticleModel::getTable(), $articleModel->id, $delete);
        $this->deleteRecords($delete, $articleModel->id, $dataContainer);
    }

    /**
     * Recursively get all related table names and records which has to be deleted.
     *
     * @param string  $table    The current table name.
     * @param integer $recordId The record id.
     * @param array   $delete   Array of all collected delete statements.
     *
     * @return void
     */
    private function collectChildren($table, $recordId, &$delete): void
    {
        $cctable = [];
        $ctables = $this->dcaManager->getDefinition($table)->get(['config', 'ctable']);

        if (!\is_array($ctables)) {
            return;
        }

        // Walk through each child table
        foreach ($ctables as $ctable) {
            if (!strlen($ctable)) {
                continue;
            }

            $definition       = $this->dcaManager->getDefinition($ctable);
            $cctable[$ctable] = $definition->get(['config', 'ctable']);

            $builder = $this->buildCollectChildrenQuery($definition, $recordId, $ctable);
            $result  = $builder->execute();

            if ($definition->get(['config', 'doNotDeleteRecords']) || !$result->rowCount()) {
                continue;
            }

            while ($deleteId = $result->fetchColumn()) {
                $delete[$ctable][] = $deleteId;

                if (!empty($cctable[$ctable])) {
                    $this->collectChildren($ctable, $deleteId, $delete);
                }
            }
        }
    }

    /**
     * Delete all records.
     *
     * @param array         $delete        Records to delete.
     * @param int           $articleId     The article id.
     * @param DataContainer $dataContainer Data container driver of tl_page.
     *
     * @return void
     *
     * @throws InvalidArgumentException When an invalid argument is passed to the delete statement.
     */
    private function deleteRecords(array $delete, $articleId, $dataContainer): void
    {
        $connection   = $this->repositoryManager->getConnection();
        $affectedRows = $this->updateUndoRecord($delete, $articleId);

        // Delete the records
        if (!$affectedRows) {
            return;
        }

        $undoId    = $connection->lastInsertId();
        $callbacks = $this->dcaManager->getDefinition(ArticleModel::getTable())->get(['config', 'ondelete_callback']);

        if ($callbacks) {
            $this->callbackInvoker->invokeAll($callbacks, [$dataContainer, $undoId]);
        }

        foreach ($delete as $table => $records) {
            foreach ($records as $recordId) {
                $connection->delete($table, ['id' => $recordId]);
            }
        }
    }

    /**
     * Update the undo record with all child content.
     *
     * @param array $delete    Records to delete.
     * @param int   $articleId The article id.
     *
     * @return int
     */
    private function updateUndoRecord(array $delete, $articleId): int
    {
        $connection = $this->repositoryManager->getConnection();
        $affected   = 0;
        $data       = [];

        // Save each record of each table
        foreach ($delete as $table => $fields) {
            foreach ($fields as $key => $value) {
                $statement = $connection->createQueryBuilder()
                    ->select('*')
                    ->from($table)
                    ->where('id=:id')
                    ->setParameter('id', $value)
                    ->execute();

                if ($statement->rowCount()) {
                    $data[$table][$key] = $statement->fetch(\PDO::FETCH_ASSOC);
                    $affected++;
                }
            }
        }

        return $connection->insert(
            'tl_undo',
            [
                'pid'          => $this->backendUser->id,
                'tstamp'       => time(),
                'fromTable'    => ArticleModel::getTable(),
                'query'        => 'DELETE FROM tl_article WHERE id=' . $articleId,
                'affectedRows' => $affected,
                'data'         => serialize($data),
            ]
        );
    }

    /**
     * Build the children collection query.
     *
     * @param Definition $definition The dca definition.
     * @param string|int $recordId   The record id.
     * @param string     $ctable     The children table.
     *
     * @return QueryBuilder
     */
    private function buildCollectChildrenQuery($definition, $recordId, $ctable): QueryBuilder
    {
        $builder = $this->repositoryManager->getConnection()->createQueryBuilder()
            ->select('id')
            ->from($ctable)
            ->andWhere('pid=:pid')
            ->setParameter('pid', $recordId);

        // Consider the dynamic parent table (see #4867)
        if ($definition->get(['config', 'dynamicPtable'])) {
            $ptable = $definition->get(['config', 'ptable']);
            $builder->setParameter('ptable', $ptable);

            if ($ptable === 'tl_article') {
                $builder->andWhere('(ptable=:ptable OR ptable=\'\')');
            } else {
                $builder->andWhere('ptable=:ptable');
            }
        }

        return $builder;
    }
}
