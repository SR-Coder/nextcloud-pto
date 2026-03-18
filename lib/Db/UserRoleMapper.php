<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class UserRoleMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'pto_user_roles', UserRole::class);
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): UserRole {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * @throws DoesNotExistException
     */
    public function findByUser(string $userId): UserRole {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntity($qb);
    }

    /**
     * @return UserRole[]
     */
    public function findByRole(string $role): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('role', $qb->createNamedParameter($role)));

        return $this->findEntities($qb);
    }

    /**
     * @return UserRole[]
     */
    public function findByManager(string $managerId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('manager_id', $qb->createNamedParameter($managerId)));

        return $this->findEntities($qb);
    }

    /**
     * @return UserRole[]
     */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName());

        return $this->findEntities($qb);
    }
}
