<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class PolicyMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'pto_policies', Policy::class);
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): Policy {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * @return Policy[]
     */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy('name', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * @return Policy[]
     */
    public function findEnabled(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('enabled', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('name', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * @return Policy[]
     */
    public function findByType(string $type): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('type', $qb->createNamedParameter($type)))
            ->orderBy('name', 'ASC');

        return $this->findEntities($qb);
    }
}
