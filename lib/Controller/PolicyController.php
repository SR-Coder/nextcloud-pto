<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Service\AuthorizationService;
use OCA\PTO\Service\PolicyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class PolicyController extends Controller {
    private PolicyService $service;
    private AuthorizationService $authService;
    private IUserSession $userSession;

    public function __construct(
        IRequest $request,
        PolicyService $service,
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
     * List all policies (any authenticated user can view)
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): DataResponse {
        $policies = $this->service->findAll();
        return new DataResponse($policies);
    }

    /**
     * Show a single policy (any authenticated user can view)
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(int $id): DataResponse {
        try {
            $policy = $this->service->find($id);
            return new DataResponse($policy);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Create a policy - admin only
     * @NoAdminRequired
     */
    public function create(): DataResponse {
        try {
            if (!$this->authService->isAdmin($this->getUserId())) {
                return new DataResponse(['error' => 'Only administrators can create policies'], Http::STATUS_FORBIDDEN);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $policy = $this->service->create(
                $data['name'],
                $data['type'],
                $data['accrualRate'] ?? null,
                $data['accrualPeriod'] ?? null,
                $data['maxBalance'] ?? null,
                $data['fixedAnnualHours'] ?? null,
                $data['resetDate'] ?? null
            );

            return new DataResponse($policy, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Update a policy - admin only
     * @NoAdminRequired
     */
    public function update(int $id): DataResponse {
        try {
            if (!$this->authService->isAdmin($this->getUserId())) {
                return new DataResponse(['error' => 'Only administrators can update policies'], Http::STATUS_FORBIDDEN);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $policy = $this->service->update(
                $id,
                $data['name'] ?? null,
                $data['type'] ?? null,
                $data['accrualRate'] ?? null,
                $data['accrualPeriod'] ?? null,
                $data['maxBalance'] ?? null,
                $data['fixedAnnualHours'] ?? null,
                $data['resetDate'] ?? null,
                $data['enabled'] ?? null
            );

            return new DataResponse($policy);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Delete a policy - admin only
     * @NoAdminRequired
     */
    public function destroy(int $id): DataResponse {
        try {
            if (!$this->authService->isAdmin($this->getUserId())) {
                return new DataResponse(['error' => 'Only administrators can delete policies'], Http::STATUS_FORBIDDEN);
            }

            $this->service->delete($id);
            return new DataResponse([], Http::STATUS_NO_CONTENT);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }
}
