<?php

declare(strict_types=1);

namespace OCA\PTO\Service;

use DateTime;
use OCA\PTO\Db\Balance;
use OCA\PTO\Db\BalanceMapper;
use OCA\PTO\Db\Policy;
use OCA\PTO\Db\PolicyMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class BalanceService {
    private BalanceMapper $balanceMapper;
    private PolicyMapper $policyMapper;
    private PolicyService $policyService;

    public function __construct(
        BalanceMapper $balanceMapper,
        PolicyMapper $policyMapper,
        PolicyService $policyService
    ) {
        $this->balanceMapper = $balanceMapper;
        $this->policyMapper = $policyMapper;
        $this->policyService = $policyService;
    }

    /**
     * Get balance for user and policy, creating if not exists
     */
    public function getBalance(string $userId, int $policyId): Balance {
        try {
            return $this->balanceMapper->findByUserAndPolicy($userId, $policyId);
        } catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
            // Create new balance
            return $this->createBalance($userId, $policyId);
        }
    }

    /**
     * Get all balances for a user
     * @return Balance[]
     */
    public function getUserBalances(string $userId): array {
        return $this->balanceMapper->findByUser($userId);
    }

    /**
     * Create a new balance entry for a user
     */
    public function createBalance(string $userId, int $policyId, float $initialBalance = 0.0): Balance {
        $balance = new Balance();
        $balance->setUserId($userId);
        $balance->setPolicyId($policyId);
        $balance->setBalance($initialBalance);
        $balance->setAccruedThisPeriod(0.0);
        $balance->setUsedThisYear(0.0);
        $balance->setLastAccrualDate(null);
        $balance->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->balanceMapper->insert($balance);
    }

    /**
     * Process accrual for a specific user/policy
     * @throws DoesNotExistException
     */
    public function processAccrual(string $userId, int $policyId): Balance {
        $balance = $this->getBalance($userId, $policyId);
        $policy = $this->policyMapper->find($policyId);

        if ($policy->getType() !== 'accrual') {
            return $balance; // Only process accrual-type policies
        }

        $now = new DateTime();
        $lastAccrualDate = $balance->getLastAccrualDate()
            ? new DateTime($balance->getLastAccrualDate())
            : new DateTime(); // First accrual

        $accruedHours = $this->policyService->calculateAccrual($policy, $lastAccrualDate, $now);

        if ($accruedHours > 0) {
            $newBalance = $balance->getBalance() + $accruedHours;

            // Check max balance
            if ($this->policyService->exceedsMaxBalance($policy, $balance->getBalance(), $accruedHours)) {
                $maxBalance = $policy->getMaxBalance();
                $accruedHours = max(0, $maxBalance - $balance->getBalance());
                $newBalance = $maxBalance;
            }

            $balance->setBalance($newBalance);
            $balance->setAccruedThisPeriod($balance->getAccruedThisPeriod() + $accruedHours);
            $balance->setLastAccrualDate($now->format('Y-m-d H:i:s'));
            $balance->setUpdatedAt($now->format('Y-m-d H:i:s'));

            return $this->balanceMapper->update($balance);
        }

        return $balance;
    }

    /**
     * Deduct hours from balance (when request is approved)
     * @throws DoesNotExistException
     */
    public function deductHours(string $userId, int $policyId, float $hours): Balance {
        $balance = $this->getBalance($userId, $policyId);

        $newBalance = max(0, $balance->getBalance() - $hours);
        $balance->setBalance($newBalance);
        $balance->setUsedThisYear($balance->getUsedThisYear() + $hours);
        $balance->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->balanceMapper->update($balance);
    }

    /**
     * Restore hours to balance (when request is cancelled/denied)
     * @throws DoesNotExistException
     */
    public function restoreHours(string $userId, int $policyId, float $hours): Balance {
        $balance = $this->getBalance($userId, $policyId);

        $balance->setBalance($balance->getBalance() + $hours);
        $balance->setUsedThisYear(max(0, $balance->getUsedThisYear() - $hours));
        $balance->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        return $this->balanceMapper->update($balance);
    }

    /**
     * Check if user has sufficient balance for a request
     */
    public function hasSufficientBalance(string $userId, int $policyId, float $requestedHours): bool {
        try {
            $balance = $this->getBalance($userId, $policyId);
            $policy = $this->policyMapper->find($policyId);

            // Unlimited policies always have sufficient balance
            if ($policy->getType() === 'unlimited') {
                return true;
            }

            return $balance->getBalance() >= $requestedHours;
        } catch (DoesNotExistException $e) {
            return false;
        }
    }

    /**
     * Get all balances for a user with policy information
     * @return array[]
     */
    public function getUserBalancesWithPolicies(string $userId): array {
        $balances = $this->balanceMapper->findByUser($userId);
        $result = [];

        foreach ($balances as $balance) {
            try {
                $policy = $this->policyMapper->find($balance->getPolicyId());
                
                $result[] = [
                    'policyId' => $policy->getId(),
                    'policyName' => $policy->getName(),
                    'policyType' => $policy->getType(),
                    'availableBalance' => $balance->getBalance(),
                    'usedBalance' => $balance->getUsedThisYear(),
                    'pendingBalance' => 0.0, // TODO: Calculate from pending requests
                    'accrualRate' => $policy->getAccrualRate(),
                    'accrualPeriod' => $policy->getAccrualPeriod(),
                    'maxBalance' => $policy->getMaxBalance(),
                ];
            } catch (DoesNotExistException $e) {
                // Skip balances for deleted policies
                continue;
            }
        }

        return $result;
    }

    /**
     * Process accrual for all users with a specific policy
     * Called by background job
     * @return int Number of balances processed
     */
    public function processAccrualForPolicy(int $policyId): int {
        // Get all balances for this policy
        $balances = $this->balanceMapper->findByPolicy($policyId);
        $processed = 0;

        foreach ($balances as $balance) {
            try {
                $this->processAccrual($balance->getUserId(), $policyId);
                $processed++;
            } catch (\Exception $e) {
                // Log error but continue processing others
                // TODO: Log to system logger
                continue;
            }
        }

        return $processed;
    }
}
