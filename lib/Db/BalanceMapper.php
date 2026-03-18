<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class BalanceMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'pto_balances', Balance::class);
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): Balance {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * @throws DoesNotExistException
     */
    public function findByUserAndPolicy(string $userId, int $policyId): Balance {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('policy_id', $qb->createNamedParameter($policyId, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * @return Balance[]
     */
    public function findByUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntities($qb);
    }

    /**
     * @return Balance[]
     */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName());

        return $this->findEntities($qb);
    }
}
