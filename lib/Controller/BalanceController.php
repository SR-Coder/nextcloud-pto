<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Service\BalanceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class BalanceController extends Controller {
    private BalanceService $service;
    private IUserSession $userSession;

    public function __construct(
        IRequest $request,
        BalanceService $service,
        IUserSession $userSession
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->service = $service;
        $this->userSession = $userSession;
    }

    private function getUserId(): string {
        return $this->userSession->getUser()->getUID();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): DataResponse {
        $userId = $this->getUserId();
        $balances = $this->service->getUserBalancesWithPolicies($userId);

        return new DataResponse(['balances' => $balances]);
    }

    /**
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
     * @NoCSRFRequired
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
     * Assign a policy to a user (create balance)
     * Admin only
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function assignPolicy(string $userId, int $policyId, float $initialBalance = 0.0): DataResponse {
        try {
            // TODO: Check admin permission
            $balance = $this->service->createBalance($userId, $policyId, $initialBalance);

            return new DataResponse($balance, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }
}
