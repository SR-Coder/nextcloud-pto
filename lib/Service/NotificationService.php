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
        // Ensure notifications app is loaded so its IApp is registered
        \OC_App::loadApp('notifications');
        
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

            foreach ($managerIds as $managerId) {
                // Don't notify if manager is the requester
                if ($managerId === $request->getUserId()) {
                    continue;
                }

                try {
                    $notification = $this->notificationManager->createNotification();
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

                    $this->notificationManager->notify($notification);
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
        \OC_App::loadApp('notifications');
        
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
        \OC_App::loadApp('notifications');
        
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
     * Notify manager when an employee cancels their request
     */
    public function notifyRequestCancelled(Request $request): void {
        \OC_App::loadApp('notifications');
        
        $requester = $this->userManager->get($request->getUserId());
        if ($requester === null) {
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

        foreach ($managerIds as $managerId) {
            // Don't notify if manager is the requester
            if ($managerId === $request->getUserId()) {
                continue;
            }

            try {
                $notification = $this->notificationManager->createNotification();
                $notification->setApp(Application::APP_ID)
                    ->setUser($managerId)
                    ->setDateTime(new \DateTime())
                    ->setObject('request', (string)$request->getId())
                    ->setSubject('request_cancelled', [
                        'requestId' => $request->getId(),
                        'requester' => $requester->getDisplayName(),
                        'hours' => $request->getHours(),
                        'startDate' => $request->getStartDate(),
                        'endDate' => $request->getEndDate(),
                    ]);

                $this->notificationManager->notify($notification);
            } catch (\Exception $e) {
                \OC::$server->getLogger()->error('Failed to send cancel notification', [
                    'app' => 'pto',
                    'managerId' => $managerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Note: We don't call removeRequestNotifications() here because we want
        // managers to see the cancellation notice, not have it immediately removed
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
