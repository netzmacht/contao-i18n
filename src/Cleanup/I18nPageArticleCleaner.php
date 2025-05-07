<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\Cleanup;

use Contao\ArticleModel;
use Contao\BackendUser;
use Contao\DataContainer;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Query\QueryBuilder;
use Netzmacht\Contao\Toolkit\Callback\Invoker;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Dca\DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Definition;

use function assert;
use function is_array;
use function serialize;
use function strlen;
use function time;

final class I18nPageArticleCleaner
{
    /**
     * @param RepositoryManager $repositoryManager Repository manager.
     * @param DcaManager        $dcaManager        Repository manager.
     * @param BackendUser       $backendUser       Backend user adapter.
     * @param Invoker           $callbackInvoker   Callback invoker.
     */
    public function __construct(
        private readonly RepositoryManager $repositoryManager,
        private readonly DcaManager $dcaManager,
        private readonly BackendUser $backendUser,
        private readonly Invoker $callbackInvoker,
    ) {
    }

    /**
     * Clean up articles of the page.
     *
     * @param DataContainer $dataContainer Data container driver of tl_page.
     *
     * @throws InvalidArgumentException When an invalid argument is passed to the delete statement.
     */
    public function cleanupUnrelatedArticles(DataContainer $dataContainer): void
    {
        $articleRepository = $this->repositoryManager->getRepository(ArticleModel::class);
        $collection        = $articleRepository->findBy(['.pid=?', '.languageMain=0'], [$dataContainer->id]);

        foreach ($collection ?? [] as $articleModel) {
            assert($articleModel instanceof ArticleModel);
            $this->deleteArticle($articleModel, $dataContainer);
        }
    }

    /**
     * Delete a article. The article is listed
     *
     * @param ArticleModel  $articleModel  Article model.
     * @param DataContainer $dataContainer Data container driver of tl_page.
     *
     * @throws InvalidArgumentException When an invalid argument is passed to the delete statement.
     */
    public function deleteArticle(ArticleModel $articleModel, DataContainer $dataContainer): void
    {
        $delete = [
            ArticleModel::getTable() => [(int) $articleModel->id],
        ];

        $this->collectChildren(ArticleModel::getTable(), (int) $articleModel->id, $delete);
        $this->deleteRecords($delete, (int) $articleModel->id, $dataContainer);
    }

    /**
     * Recursively get all related table names and records which has to be deleted.
     *
     * @param string                         $table    The current table name.
     * @param int                            $recordId The record id.
     * @param array<string,list<int|string>> $delete   Array of all collected delete statements.
     */
    private function collectChildren(string $table, int $recordId, array &$delete): void
    {
        $cctable = [];
        $ctables = $this->dcaManager->getDefinition($table)->get(['config', 'ctable']);

        if (! is_array($ctables)) {
            return;
        }

        // Walk through each child table
        foreach ($ctables as $ctable) {
            if (! strlen($ctable)) {
                continue;
            }

            $definition       = $this->dcaManager->getDefinition($ctable);
            $cctable[$ctable] = $definition->get(['config', 'ctable']);

            $builder = $this->buildCollectChildrenQuery($definition, $recordId, $ctable);
            $result  = $builder->executeQuery();

            if ($definition->get(['config', 'doNotDeleteRecords']) || ! $result->rowCount()) {
                continue;
            }

            while ($deleteId = $result->fetchOne()) {
                $delete[$ctable][] = (int) $deleteId;

                if (empty($cctable[$ctable])) {
                    continue;
                }

                $this->collectChildren($ctable, $deleteId, $delete);
            }
        }
    }

    /**
     * Delete all records.
     *
     * @param array<string,list<int|string>> $delete        Records to delete.
     * @param int                            $articleId     The article id.
     * @param DataContainer                  $dataContainer Data container driver of tl_page.
     *
     * @throws InvalidArgumentException When an invalid argument is passed to the delete statement.
     */
    private function deleteRecords(array $delete, int $articleId, DataContainer $dataContainer): void
    {
        $connection   = $this->repositoryManager->getConnection();
        $affectedRows = $this->updateUndoRecord($delete, $articleId);

        // Delete the records
        if (! $affectedRows) {
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
     * @param array<string,list<int|string>> $delete    Records to delete.
     * @param int                            $articleId The article id.
     */
    private function updateUndoRecord(array $delete, int $articleId): int
    {
        $connection = $this->repositoryManager->getConnection();
        $affected   = 0;
        $data       = [];

        // Save each record of each table
        foreach ($delete as $table => $fields) {
            foreach ($fields as $value) {
                $result = $connection->createQueryBuilder()
                    ->select('*')
                    ->from($table)
                    ->where('id=:id')
                    ->setParameter('id', $value)
                    ->executeQuery();

                if (! $result->rowCount()) {
                    continue;
                }

                $data[$table][$value] = $result->fetchAssociative();
                $affected++;
            }
        }

        $connection->insert(
            'tl_undo',
            [
                'pid'          => $this->backendUser->id,
                'tstamp'       => time(),
                'fromTable'    => ArticleModel::getTable(),
                'query'        => 'DELETE FROM tl_article WHERE id=' . $articleId,
                'affectedRows' => $affected,
                'data'         => serialize($data),
            ],
        );

        return (int) $connection->lastInsertId();
    }

    /**
     * Build the children collection query.
     *
     * @param Definition $definition The dca definition.
     * @param int        $recordId   The record id.
     * @param string     $ctable     The children table.
     */
    private function buildCollectChildrenQuery(Definition $definition, int $recordId, string $ctable): QueryBuilder
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
