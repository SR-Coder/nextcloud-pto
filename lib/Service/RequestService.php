<?php

declare(strict_types=1);

namespace OCA\PTO\Service;

use DateTime;
use Exception;
use OCA\PTO\Db\Approval;
use OCA\PTO\Db\ApprovalMapper;
use OCA\PTO\Db\Request;
use OCA\PTO\Db\RequestMapper;
use OCA\PTO\Db\UserRoleMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class RequestService {
    private RequestMapper $requestMapper;
    private ApprovalMapper $approvalMapper;
    private UserRoleMapper $userRoleMapper;
    private BalanceService $balanceService;
    private AuthorizationService $authService;
    private NotificationService $notificationService;

    public function __construct(
        RequestMapper $requestMapper,
        ApprovalMapper $approvalMapper,
        UserRoleMapper $userRoleMapper,
        BalanceService $balanceService,
        AuthorizationService $authService,
        NotificationService $notificationService
    ) {
        $this->requestMapper = $requestMapper;
        $this->approvalMapper = $approvalMapper;
        $this->userRoleMapper = $userRoleMapper;
        $this->balanceService = $balanceService;
        $this->authService = $authService;
        $this->notificationService = $notificationService;
    }

    /**
     * @throws DoesNotExistException
     */
    public function find(int $id): Request {
        return $this->requestMapper->find($id);
    }

    /**
     * @return Request[]
     */
    public function findByUser(string $userId, ?string $status = null): array {
        return $this->requestMapper->findByUser($userId, $status);
    }

    /**
     * Get pending requests for users this manager manages
     * Uses Nextcloud's native manager relationships
     * @return Request[]
     */
    public function findPendingForManager(string $managerId): array {
        // If admin, return all pending requests
        if ($this->authService->isAdmin($managerId)) {
            return $this->requestMapper->findPending();
        }
        
        // Get all users this manager manages
        $managedUserIds = $this->authService->getManagedUsers($managerId);
        
        if (empty($managedUserIds)) {
            return [];
        }
        
        // Get pending requests for those users
        return $this->requestMapper->findByUsers($managedUserIds, 'pending');
    }

    /**
     * Create a new PTO request
     * @throws Exception
     */
    public function createRequest(
        string $userId,
        int $policyId,
        string $leaveType,
        string $startDate,
        string $endDate,
        float $hours,
        ?string $notes = null,
        ?string $submittedBy = null
    ): Request {
        // Validate dates
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        if ($end < $start) {
            throw new Exception('End date must be after start date');
        }

        // Check balance
        if (!$this->balanceService->hasSufficientBalance($userId, $policyId, $hours)) {
            throw new Exception('Insufficient PTO balance');
        }

        $request = new Request();
        $request->setUserId($userId);
        $request->setPolicyId($policyId);
        $request->setLeaveType($leaveType);
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setHours($hours);
        $request->setStatus('pending');
        $request->setNotes($notes);
        $request->setSubmittedBy($submittedBy ?? $userId);
        $request->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $request->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));

        $createdRequest = $this->requestMapper->insert($request);
        
        // Send notification to managers
        $this->notificationService->notifyRequestSubmitted($createdRequest);

        return $createdRequest;
    }

    /**
     * Approve a request
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function approveRequest(int $requestId, string $managerId, ?string $comments = null): Request {
        $request = $this->requestMapper->find($requestId);

        if ($request->getStatus() !== 'pending') {
            throw new Exception('Request is not pending');
        }

        // Verify manager has permission
        if (!$this->isManager($managerId, $request->getUserId())) {
            throw new Exception('Not authorized to approve this request');
        }

        // Update request status
        $request->setStatus('approved');
        $request->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->requestMapper->update($request);

        // Record approval
        $approval = new Approval();
        $approval->setRequestId($requestId);
        $approval->setManagerId($managerId);
        $approval->setAction('approved');
        $approval->setComments($comments);
        $approval->setActedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->approvalMapper->insert($approval);

        // Deduct from balance
        $this->balanceService->deductHours(
            $request->getUserId(),
            $request->getPolicyId(),
            $request->getHours()
        );

        // Send notification to requester
        $this->notificationService->notifyRequestApproved($request, $comments);

        // TODO: Create calendar event

        return $request;
    }

    /**
     * Deny a request
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function denyRequest(int $requestId, string $managerId, ?string $comments = null): Request {
        $request = $this->requestMapper->find($requestId);

        if ($request->getStatus() !== 'pending') {
            throw new Exception('Request is not pending');
        }

        // Verify manager has permission
        if (!$this->isManager($managerId, $request->getUserId())) {
            throw new Exception('Not authorized to deny this request');
        }

        // Update request status
        $request->setStatus('denied');
        $request->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->requestMapper->update($request);

        // Record denial
        $approval = new Approval();
        $approval->setRequestId($requestId);
        $approval->setManagerId($managerId);
        $approval->setAction('denied');
        $approval->setComments($comments);
        $approval->setActedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->approvalMapper->insert($approval);

        // Send notification to requester
        $this->notificationService->notifyRequestDenied($request, $comments);

        return $request;
    }

    /**
     * Cancel a request (employee-initiated)
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function cancelRequest(int $requestId, string $userId): Request {
        $request = $this->requestMapper->find($requestId);

        // Only the user who submitted can cancel
        if ($request->getUserId() !== $userId && $request->getSubmittedBy() !== $userId) {
            throw new Exception('Not authorized to cancel this request');
        }

        $wasApproved = $request->getStatus() === 'approved';

        $request->setStatus('cancelled');
        $request->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
        $this->requestMapper->update($request);

        // If was approved, restore balance
        if ($wasApproved) {
            $this->balanceService->restoreHours(
                $request->getUserId(),
                $request->getPolicyId(),
                $request->getHours()
            );

            // TODO: Delete calendar event
        }

        return $request;
    }

    /**
     * Check if a user is a manager for another user
     * Uses Nextcloud's native manager relationships
     */
    private function isManager(string $managerId, string $userId): bool {
        // Admins can approve any request
        if ($this->authService->isAdmin($managerId)) {
            return true;
        }
        
        // Check if manager is in the user's manager list
        return $this->authService->isManagerOf($managerId, $userId);
    }

    /**
     * Get approval history for a request
     * @return Approval[]
     */
    public function getApprovals(int $requestId): array {
        return $this->approvalMapper->findByRequest($requestId);
    }
}
