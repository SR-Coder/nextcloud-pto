<?php

declare(strict_types=1);

namespace OCA\PTO\BackgroundJob;

use OCA\PTO\Db\PolicyMapper;
use OCA\PTO\Service\BalanceService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Background job to process PTO accruals for all active policies
 * Runs daily to add accrued hours to user balances
 */
class AccrualJob extends TimedJob {
    private BalanceService $balanceService;
    private PolicyMapper $policyMapper;
    private LoggerInterface $logger;

    public function __construct(
        ITimeFactory $time,
        BalanceService $balanceService,
        PolicyMapper $policyMapper,
        LoggerInterface $logger
    ) {
        parent::__construct($time);
        
        // Run once per day (86400 seconds)
        $this->setInterval(86400);
        
        $this->balanceService = $balanceService;
        $this->policyMapper = $policyMapper;
        $this->logger = $logger;
    }

    /**
     * Process accruals for all users
     */
    protected function run($argument): void {
        $this->logger->info('PTO accrual job started');
        
        try {
            // Get all active accrual policies
            $policies = $this->policyMapper->findAll();
            $accrualPolicies = array_filter($policies, function($policy) {
                return $policy->getType() === 'accrual' && $policy->getEnabled();
            });

            $processed = 0;
            $errors = 0;

            foreach ($accrualPolicies as $policy) {
                try {
                    // Process accrual for all users with this policy
                    $count = $this->balanceService->processAccrualForPolicy($policy->getId());
                    $processed += $count;
                    
                    $this->logger->debug('Processed accrual for policy', [
                        'policyId' => $policy->getId(),
                        'policyName' => $policy->getName(),
                        'userCount' => $count,
                    ]);
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->error('Error processing accrual for policy', [
                        'policyId' => $policy->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('PTO accrual job completed', [
                'processed' => $processed,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('PTO accrual job failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
