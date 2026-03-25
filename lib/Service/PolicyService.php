<?php

declare(strict_types=1);

namespace OCA\PTO\Service;

use DateTime;
use OCA\PTO\Db\Policy;
use OCA\PTO\Db\PolicyMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class PolicyService {
    private PolicyMapper $mapper;

    public function __construct(PolicyMapper $mapper) {
        $this->mapper = $mapper;
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): Policy {
        return $this->mapper->find($id);
    }

    /**
     * @return Policy[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @return Policy[]
     */
    public function findEnabled(): array {
        return $this->mapper->findEnabled();
    }

    public function create(
        string $name,
        string $type,
        ?float $accrualRate = null,
        ?string $accrualPeriod = null,
        ?float $maxBalance = null,
        ?float $fixedAnnualHours = null,
        ?string $resetDate = null
    ): Policy {
        $policy = new Policy();
        $policy->setName($name);
        $policy->setType($type);
        $policy->setAccrualRate($accrualRate);
        $policy->setAccrualPeriod($accrualPeriod);
        $policy->setMaxBalance($maxBalance);
        $policy->setFixedAnnualHours($fixedAnnualHours);
        $policy->setResetDate($resetDate);
        $policy->setEnabled(true);
        $policy->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $policy->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->mapper->insert($policy);
    }

    /**
     * @throws DoesNotExistException
     */
    public function update(
        int $id,
        ?string $name = null,
        ?string $type = null,
        ?float $accrualRate = null,
        ?string $accrualPeriod = null,
        ?float $maxBalance = null,
        ?float $fixedAnnualHours = null,
        ?string $resetDate = null,
        ?bool $enabled = null
    ): Policy {
        $policy = $this->mapper->find($id);

        if ($name !== null) {
            $policy->setName($name);
        }
        if ($type !== null) {
            $policy->setType($type);
        }
        if ($accrualRate !== null) {
            $policy->setAccrualRate($accrualRate);
        }
        if ($accrualPeriod !== null) {
            $policy->setAccrualPeriod($accrualPeriod);
        }
        if ($maxBalance !== null) {
            $policy->setMaxBalance($maxBalance);
        }
        if ($fixedAnnualHours !== null) {
            $policy->setFixedAnnualHours($fixedAnnualHours);
        }
        if ($resetDate !== null) {
            $policy->setResetDate($resetDate);
        }
        if ($enabled !== null) {
            $policy->setEnabled($enabled);
        }

        $policy->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->mapper->update($policy);
    }

    /**
     * @throws DoesNotExistException
     */
    public function delete(int $id): Policy {
        $policy = $this->mapper->find($id);
        $this->mapper->delete($policy);
        return $policy;
    }

    /**
     * Calculate hours to accrue based on policy and time period
     */
    public function calculateAccrual(Policy $policy, DateTime $lastAccrualDate, DateTime $currentDate): float {
        if ($policy->getType() !== 'accrual') {
            return 0.0;
        }

        $accrualRate = $policy->getAccrualRate() ?? 0.0;
        $accrualPeriod = $policy->getAccrualPeriod();

        if ($accrualRate === 0.0 || $accrualPeriod === null) {
            return 0.0;
        }

        $interval = $lastAccrualDate->diff($currentDate);

        switch ($accrualPeriod) {
            case 'daily':
                return $interval->days * $accrualRate;
            case 'weekly':
                return floor($interval->days / 7) * $accrualRate;
            case 'monthly':
                $months = ($interval->y * 12) + $interval->m;
                return $months * $accrualRate;
            case 'yearly':
                return $interval->y * $accrualRate;
            default:
                return 0.0;
        }
    }

    /**
     * Check if a balance exceeds the policy's max balance
     */
    public function exceedsMaxBalance(Policy $policy, float $currentBalance, float $toAdd): bool {
        $maxBalance = $policy->getMaxBalance();

        if ($maxBalance === null) {
            return false; // No max balance set
        }

        return ($currentBalance + $toAdd) > $maxBalance;
    }
}
