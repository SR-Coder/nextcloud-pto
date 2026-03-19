<?php

declare(strict_types=1);

namespace OCA\PTO\Service;

use OCA\PTO\AppInfo\Application;
use OCA\PTO\Db\Request;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Notification\IManager;

class NotificationService {
    private IManager $notificationManager;
    private IUserManager $userManager;
    private IGroupManager $groupManager;
    private AuthorizationService $authService;

    public function __construct(
        IManager $notificationManager,
        IUserManager $userManager,
        IGroupManager $groupManager,
        AuthorizationService $authService
    ) {
        $this->notificationManager = $notificationManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->authService = $authService;
    }

    /**
     * Notify managers when a request is submitted
     */
    public function notifyRequestSubmitted(Request $request): void {
        error_log("[PTO DEBUG] notifyRequestSubmitted called for request ID: " . $request->getId());
        
        try {
            $requester = $this->userManager->get($request->getUserId());
            if ($requester === null) {
                \OC::$server->getLogger()->warning('Cannot send notification: requester not found', [
                    'app' => 'pto',
                    'userId' => $request->getUserId()
                ]);
                return;
            }

            // Get all managers for this user
            $managerIds = $this->authService->getManagersFor($request->getUserId());
            
            // Also notify admins
            $adminGroup = $this->groupManager->get('admin');
            $admins = $adminGroup ? $adminGroup->getUsers() : [];
            foreach ($admins as $admin) {
                $managerIds[] = $admin->getUID();
            }

            // Remove duplicates
            $managerIds = array_unique($managerIds);

            \OC::$server->getLogger()->info('Sending PTO request notifications', [
                'app' => 'pto',
                'requestId' => $request->getId(),
                'managerCount' => count($managerIds),
                'managers' => $managerIds
            ]);

            foreach ($managerIds as $managerId) {
                // Don't notify if manager is the requester
                if ($managerId === $request->getUserId()) {
                    \OC::$server->getLogger()->info('Skipping self-notification', [
                        'app' => 'pto',
                        'managerId' => $managerId,
                        'requestUserId' => $request->getUserId()
                    ]);
                    continue;
                }

                try {
                    \OC::$server->getLogger()->info('Creating notification object', [
                        'app' => 'pto',
                        'managerId' => $managerId,
                        'requestId' => $request->getId()
                    ]);

                    $notification = $this->notificationManager->createNotification();
                    
                    \OC::$server->getLogger()->info('Setting notification fields', [
                        'app' => 'pto',
                        'fields' => [
                            'app' => Application::APP_ID,
                            'user' => $managerId,
                            'objectType' => 'request',
                            'objectId' => (string)$request->getId(),
                            'subject' => 'request_submitted'
                        ]
                    ]);
                    
                    $notification->setApp(Application::APP_ID)
                        ->setUser($managerId)
                        ->setDateTime(new \DateTime())
                        ->setObject('request', (string)$request->getId())
                        ->setSubject('request_submitted', [
                            'requestId' => $request->getId(),
                            'requester' => $requester->getDisplayName(),
                            'hours' => $request->getHours(),
                            'startDate' => $request->getStartDate(),
                            'endDate' => $request->getEndDate(),
                        ]);

                    \OC::$server->getLogger()->info('Calling notify()', [
                        'app' => 'pto',
                        'managerId' => $managerId
                    ]);

                    $this->notificationManager->notify($notification);
                    
                    \OC::$server->getLogger()->info('Successfully sent notification to manager', [
                        'app' => 'pto',
                        'managerId' => $managerId,
                        'requestId' => $request->getId()
                    ]);
                } catch (\Exception $notifyError) {
                    \OC::$server->getLogger()->error('Failed to send individual notification', [
                        'app' => 'pto',
                        'managerId' => $managerId,
                        'requestId' => $request->getId(),
                        'error' => $notifyError->getMessage(),
                        'trace' => $notifyError->getTraceAsString()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \OC::$server->getLogger()->error('Failed to send request notifications', [
                'app' => 'pto',
                'requestId' => $request->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Notify requester when request is approved
     */
    public function notifyRequestApproved(Request $request, ?string $comments = null): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setUser($request->getUserId())
            ->setDateTime(new \DateTime())
            ->setObject('request', (string)$request->getId())
            ->setSubject('request_approved', [
                'requestId' => $request->getId(),
                'hours' => $request->getHours(),
                'startDate' => $request->getStartDate(),
                'endDate' => $request->getEndDate(),
                'comments' => $comments ?? '',
            ]);

        $this->notificationManager->notify($notification);

        // Remove pending notifications for managers
        $this->removeRequestNotifications($request->getId());
    }

    /**
     * Notify requester when request is denied
     */
    public function notifyRequestDenied(Request $request, ?string $comments = null): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setUser($request->getUserId())
            ->setDateTime(new \DateTime())
            ->setObject('request', (string)$request->getId())
            ->setSubject('request_denied', [
                'requestId' => $request->getId(),
                'hours' => $request->getHours(),
                'startDate' => $request->getStartDate(),
                'endDate' => $request->getEndDate(),
                'comments' => $comments ?? '',
            ]);

        $this->notificationManager->notify($notification);

        // Remove pending notifications for managers
        $this->removeRequestNotifications($request->getId());
    }

    /**
     * Remove all notifications for a specific request (e.g., when approved/denied)
     */
    private function removeRequestNotifications(int $requestId): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setObject('request', (string)$requestId);

        $this->notificationManager->markProcessed($notification);
    }
}
