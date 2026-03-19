<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Service\AuthorizationService;
use OCA\PTO\Service\RequestService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class RequestController extends Controller {
    private RequestService $service;
    private AuthorizationService $authService;
    private IUserSession $userSession;

    public function __construct(
        IRequest $request,
        RequestService $service,
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
     * List current user's requests only
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(?string $status = null): DataResponse {
        $userId = $this->getUserId();
        $requests = $this->service->findByUser($userId, $status);

        return new DataResponse($requests);
    }

    /**
     * Show a single request - only if user owns it, is manager, or is admin
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(int $id): DataResponse {
        try {
            $userId = $this->getUserId();
            $request = $this->service->find($id);

            $requestUserId = $request->getUserId();
            if ($requestUserId !== $userId
                && !$this->authService->isManagerOf($userId, $requestUserId)
                && !$this->authService->isAdmin($userId)) {
                return new DataResponse(['error' => 'Access denied'], Http::STATUS_FORBIDDEN);
            }

            return new DataResponse($request);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Create a new PTO request for the current user
     * @NoAdminRequired
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
     * Approve a request - only if user is manager of requester or admin
     * @NoAdminRequired
     */
    public function approve(int $id, ?string $comments = null): DataResponse {
        try {
            $managerId = $this->getUserId();
            $request = $this->service->find($id);

            $requestUserId = $request->getUserId();
            if (!$this->authService->isManagerOf($managerId, $requestUserId)
                && !$this->authService->isAdmin($managerId)) {
                return new DataResponse(['error' => 'You are not authorized to approve this request'], Http::STATUS_FORBIDDEN);
            }

            $result = $this->service->approveRequest($id, $managerId, $comments);
            return new DataResponse($result);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Deny a request - only if user is manager of requester or admin
     * @NoAdminRequired
     */
    public function deny(int $id, ?string $comments = null): DataResponse {
        try {
            $managerId = $this->getUserId();
            $request = $this->service->find($id);

            $requestUserId = $request->getUserId();
            if (!$this->authService->isManagerOf($managerId, $requestUserId)
                && !$this->authService->isAdmin($managerId)) {
                return new DataResponse(['error' => 'You are not authorized to deny this request'], Http::STATUS_FORBIDDEN);
            }

            $result = $this->service->denyRequest($id, $managerId, $comments);
            return new DataResponse($result);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Cancel a request - only the requester can cancel their own request
     * @NoAdminRequired
     */
    public function cancel(int $id): DataResponse {
        try {
            $userId = $this->getUserId();
            $request = $this->service->find($id);

            if ($request->getUserId() !== $userId) {
                return new DataResponse(['error' => 'You can only cancel your own requests'], Http::STATUS_FORBIDDEN);
            }

            $result = $this->service->cancelRequest($id, $userId);
            return new DataResponse($result);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Get pending requests for the current user to approve
     * Only returns requests from users this person manages (or all if admin)
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function pending(): DataResponse {
        $managerId = $this->getUserId();
        $requests = $this->service->findPendingForManager($managerId);

        return new DataResponse($requests);
    }

    /**
     * Get approval history for a request - only if authorized to view the request
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function approvals(int $id): DataResponse {
        try {
            $userId = $this->getUserId();
            $request = $this->service->find($id);

            $requestUserId = $request->getUserId();
            if ($requestUserId !== $userId
                && !$this->authService->isManagerOf($userId, $requestUserId)
                && !$this->authService->isAdmin($userId)) {
                return new DataResponse(['error' => 'Access denied'], Http::STATUS_FORBIDDEN);
            }

            $approvals = $this->service->getApprovals($id);
            return new DataResponse($approvals);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        }
    }
}
