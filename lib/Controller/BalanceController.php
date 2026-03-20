<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Service\AuthorizationService;
use OCA\PTO\Service\BalanceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class BalanceController extends Controller {
    private BalanceService $service;
    private AuthorizationService $authService;
    private IUserSession $userSession;

    public function __construct(
        IRequest $request,
        BalanceService $service,
        AuthorizationService $authService,
        IUserSession $userSession
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->service = $service;
        $this->authService = $authService;
        $this->userSession = $userSession;
    }

    private function getUserId(): string {
        return $this->userSession->getUser()->getUID();
    }

    /**
     * Get current user's balances only (or any user if admin with userId param)
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(?string $userId = null): DataResponse {
        // If userId is provided, check if current user is admin
        if ($userId !== null) {
            if (!$this->authService->isAdmin($this->getUserId())) {
                return new DataResponse(['error' => 'Only administrators can view other users balances'], Http::STATUS_FORBIDDEN);
            }
            $targetUserId = $userId;
        } else {
            $targetUserId = $this->getUserId();
        }
        
        $balances = $this->service->getUserBalancesWithPolicies($targetUserId);

        return new DataResponse($balances);
    }

    /**
     * Get current user's balance for a specific policy
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(int $policyId): DataResponse {
        try {
            $userId = $this->getUserId();
            $balance = $this->service->getBalance($userId, $policyId);

            return new DataResponse($balance);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Process accrual for current user
     * @NoAdminRequired
     */
    public function processAccrual(int $policyId): DataResponse {
        try {
            $userId = $this->getUserId();
            $balance = $this->service->processAccrual($userId, $policyId);

            return new DataResponse($balance);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Assign a policy to a user (create balance) - admin only
     * @NoAdminRequired
     */
    public function assignPolicy(string $userId, int $policyId, float $initialBalance = 0.0): DataResponse {
        try {
            if (!$this->authService->isAdmin($this->getUserId())) {
                return new DataResponse(['error' => 'Only administrators can assign policies'], Http::STATUS_FORBIDDEN);
            }

            $balance = $this->service->createBalance($userId, $policyId, $initialBalance);
            return new DataResponse($balance, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Remove a policy from a user (delete balance) - admin only
     * @NoAdminRequired
     */
    public function removePolicy(string $userId, int $policyId): DataResponse {
        try {
            if (!$this->authService->isAdmin($this->getUserId())) {
                return new DataResponse(['error' => 'Only administrators can remove policies'], Http::STATUS_FORBIDDEN);
            }

            $this->service->deleteBalance($userId, $policyId);
            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }
}
