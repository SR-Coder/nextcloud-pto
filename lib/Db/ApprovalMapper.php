<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ApprovalMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'pto_approvals', Approval::class);
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): Approval {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * @return Approval[]
     */
    public function findByRequest(int $requestId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('request_id', $qb->createNamedParameter($requestId, IQueryBuilder::PARAM_INT)))
            ->orderBy('acted_at', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * @return Approval[]
     */
    public function findByManager(string $managerId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('manager_id', $qb->createNamedParameter($managerId)))
            ->orderBy('acted_at', 'DESC');

        return $this->findEntities($qb);
    }
}
