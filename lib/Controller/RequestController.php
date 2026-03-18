<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Service\RequestService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class RequestController extends Controller {
    private RequestService $service;
    private IUserSession $userSession;

    public function __construct(
        IRequest $request,
        RequestService $service,
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
    public function index(?string $status = null): DataResponse {
        $userId = $this->getUserId();
        $requests = $this->service->findByUser($userId, $status);

        return new DataResponse($requests);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(int $id): DataResponse {
        try {
            $request = $this->service->find($id);
            // TODO: Check authorization
            return new DataResponse($request);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create(
        int $policyId,
        string $leaveType,
        string $startDate,
        string $endDate,
        float $hours,
        ?string $notes = null
    ): DataResponse {
        try {
            $userId = $this->getUserId();
            $request = $this->service->createRequest(
                $userId,
                $policyId,
                $leaveType,
                $startDate,
                $endDate,
                $hours,
                $notes
            );

            return new DataResponse($request, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function approve(int $id, ?string $comments = null): DataResponse {
        try {
            $managerId = $this->getUserId();
            $request = $this->service->approveRequest($id, $managerId, $comments);

            return new DataResponse($request);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deny(int $id, ?string $comments = null): DataResponse {
        try {
            $managerId = $this->getUserId();
            $request = $this->service->denyRequest($id, $managerId, $comments);

            return new DataResponse($request);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function cancel(int $id): DataResponse {
        try {
            $userId = $this->getUserId();
            $request = $this->service->cancelRequest($id, $userId);

            return new DataResponse($request);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function pending(): DataResponse {
        $managerId = $this->getUserId();
        $requests = $this->service->findPendingForManager($managerId);

        return new DataResponse($requests);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function approvals(int $id): DataResponse {
        $approvals = $this->service->getApprovals($id);
        return new DataResponse($approvals);
    }
}
