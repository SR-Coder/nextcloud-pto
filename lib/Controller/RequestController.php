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
use Psr\Log\LoggerInterface;

class RequestController extends Controller {
    private RequestService $service;
    private AuthorizationService $authService;
    private IUserSession $userSession;
    private LoggerInterface $logger;

    public function __construct(
        IRequest $request,
        RequestService $service,
        AuthorizationService $authService,
        IUserSession $userSession,
        LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->service = $service;
        $this->authService = $authService;
        $this->userSession = $userSession;
        $this->logger = $logger;
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
            // Log the actual error but don't expose it to users
            $this->logger->error('Failed to show request: ' . $e->getMessage(), ['app' => 'pto']);
            return new DataResponse(['error' => 'Request not found'], Http::STATUS_NOT_FOUND);
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
        // Leave type is now the policy name (no validation needed)
        
        // Validate hours
        if ($hours <= 0) {
            return new DataResponse(['error' => 'Hours must be greater than 0'], Http::STATUS_BAD_REQUEST);
        }

        if ($hours > 2000) {
            return new DataResponse(['error' => 'Hours cannot exceed 2000'], Http::STATUS_BAD_REQUEST);
        }

        // Validate dates
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
        } catch (\Exception $e) {
            return new DataResponse(['error' => 'Invalid date format. Use YYYY-MM-DD'], Http::STATUS_BAD_REQUEST);
        }

        if ($start > $end) {
            return new DataResponse(['error' => 'Start date must be before or equal to end date'], Http::STATUS_BAD_REQUEST);
        }

        // Validate notes length
        if ($notes !== null && strlen($notes) > 5000) {
            return new DataResponse(['error' => 'Notes cannot exceed 5000 characters'], Http::STATUS_BAD_REQUEST);
        }

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
            $this->logger->error('Failed to create request: ' . $e->getMessage(), ['app' => 'pto']);
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Approve a request - only if user is manager of requester or admin
     * @NoAdminRequired
     */
    public function approve(int $id, ?string $comments = null): DataResponse {
        // Validate comments length
        if ($comments !== null && strlen($comments) > 2000) {
            return new DataResponse(['error' => 'Comments cannot exceed 2000 characters'], Http::STATUS_BAD_REQUEST);
        }

        try {
            $managerId = $this->getUserId();
            $request = $this->service->find($id);

            $requestUserId = $request->getUserId();
            
            // Prevent self-approval
            if ($managerId === $requestUserId) {
                return new DataResponse(['error' => 'You cannot approve your own request'], Http::STATUS_FORBIDDEN);
            }
            
            if (!$this->authService->isManagerOf($managerId, $requestUserId)
                && !$this->authService->isAdmin($managerId)) {
                return new DataResponse(['error' => 'You are not authorized to approve this request'], Http::STATUS_FORBIDDEN);
            }

            $result = $this->service->approveRequest($id, $managerId, $comments);
            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to approve request: ' . $e->getMessage(), ['app' => 'pto']);
            return new DataResponse(['error' => 'Failed to approve request. Please try again.'], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Deny a request - only if user is manager of requester or admin
     * @NoAdminRequired
     */
    public function deny(int $id, ?string $comments = null): DataResponse {
        // Validate comments length
        if ($comments !== null && strlen($comments) > 2000) {
            return new DataResponse(['error' => 'Comments cannot exceed 2000 characters'], Http::STATUS_BAD_REQUEST);
        }

        try {
            $managerId = $this->getUserId();
            $request = $this->service->find($id);

            $requestUserId = $request->getUserId();
            
            // Prevent self-denial
            if ($managerId === $requestUserId) {
                return new DataResponse(['error' => 'You cannot deny your own request. Use cancel instead.'], Http::STATUS_FORBIDDEN);
            }
            
            if (!$this->authService->isManagerOf($managerId, $requestUserId)
                && !$this->authService->isAdmin($managerId)) {
                return new DataResponse(['error' => 'You are not authorized to deny this request'], Http::STATUS_FORBIDDEN);
            }

            $result = $this->service->denyRequest($id, $managerId, $comments);
            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to deny request: ' . $e->getMessage(), ['app' => 'pto']);
            return new DataResponse(['error' => 'Failed to deny request. Please try again.'], Http::STATUS_BAD_REQUEST);
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
            $this->logger->error('Failed to cancel request: ' . $e->getMessage(), ['app' => 'pto']);
            return new DataResponse(['error' => 'Failed to cancel request. Please try again.'], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Get pending requests for the current user to approve
     * Only returns requests from users this person manages (or all if admin)
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function pending(): DataResponse {
        try {
            $managerId = $this->getUserId();
            if (!$this->authService->canApproveRequests($managerId)) {
                return new DataResponse(['error' => 'Only managers can approve requests'], Http::STATUS_FORBIDDEN);
            }

            $this->logger->info('Finding pending requests for manager: ' . $managerId, ['app' => 'pto']);
            
            $requests = $this->service->findPendingForManager($managerId);
            
            $this->logger->info('Found ' . count($requests) . ' pending requests', ['app' => 'pto']);
            
            return new DataResponse($requests);
        } catch (\Exception $e) {
            $this->logger->error('Error finding pending requests: ' . $e->getMessage(), [
                'app' => 'pto',
                'exception' => $e
            ]);
            return new DataResponse(['error' => 'Failed to load pending requests'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
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
            $this->logger->error('Failed to get approvals: ' . $e->getMessage(), ['app' => 'pto']);
            return new DataResponse(['error' => 'Failed to retrieve approval history'], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Get approval history (approved/denied requests) for the current manager
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function history(?int $limit = 50): DataResponse {
        try {
            $managerId = $this->getUserId();
            if (!$this->authService->canApproveRequests($managerId)) {
                return new DataResponse(['error' => 'Only managers can approve requests'], Http::STATUS_FORBIDDEN);
            }

            $requests = $this->service->findHistoryForManager($managerId, $limit ?? 50);
            
            return new DataResponse($requests);
        } catch (\Exception $e) {
            $this->logger->error('Error finding approval history: ' . $e->getMessage(), [
                'app' => 'pto',
                'exception' => $e
            ]);
            return new DataResponse(['error' => 'Failed to load approval history'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
