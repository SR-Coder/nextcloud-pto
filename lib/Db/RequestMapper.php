<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class RequestMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'pto_requests', Request::class);
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): Request {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * @return Request[]
     */
    public function findByUser(string $userId, ?string $status = null): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        if ($status !== null) {
            $qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($status)));
        }

        $qb->orderBy('start_date', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * @return Request[]
     */
    public function findByStatus(string $status): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('status', $qb->createNamedParameter($status)))
            ->orderBy('created_at', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * @return Request[]
     */
    public function findByManager(string $managerId, ?string $status = null): array {
        // Join with user_roles to find requests for users managed by this manager
        $qb = $this->db->getQueryBuilder();

        $qb->select('r.*')
            ->from($this->getTableName(), 'r')
            ->innerJoin('r', 'pto_user_roles', 'ur', 'r.user_id = ur.user_id')
            ->where($qb->expr()->eq('ur.manager_id', $qb->createNamedParameter($managerId)));

        if ($status !== null) {
            $qb->andWhere($qb->expr()->eq('r.status', $qb->createNamedParameter($status)));
        }

        $qb->orderBy('r.start_date', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * @return Request[]
     */
    public function findByDateRange(string $startDate, string $endDate): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->lte('start_date', $qb->createNamedParameter($endDate)))
            ->andWhere($qb->expr()->gte('end_date', $qb->createNamedParameter($startDate)))
            ->orderBy('start_date', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find requests by multiple user IDs
     * @param string[] $userIds Array of user IDs
     * @param string|null $status Optional status filter
     * @return Request[]
     */
    public function findByUsers(array $userIds, ?string $status = null): array {
        if (empty($userIds)) {
            return [];
        }

        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->in('user_id', $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));

        if ($status !== null) {
            $qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($status)));
        }

        $qb->orderBy('created_at', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * Find all pending requests
     * @return Request[]
     */
    public function findPending(): array {
        return $this->findByStatus('pending');
    }
}
