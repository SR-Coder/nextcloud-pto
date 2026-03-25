<?php

declare(strict_types=1);

namespace OCA\PTO\Service;

use OCP\IUserManager;
use OCP\IGroupManager;

/**
 * Authorization service for PTO app
 * Uses Nextcloud's native manager relationships
 */
class AuthorizationService {
    private IUserManager $userManager;
    private IGroupManager $groupManager;

    public function __construct(IUserManager $userManager, IGroupManager $groupManager) {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
    }

    /**
     * Check if userId is a manager of targetUserId
     * Uses Nextcloud's native manager relationships
     */
    public function isManagerOf(string $managerId, string $targetUserId): bool {
        $targetUser = $this->userManager->get($targetUserId);
        if ($targetUser === null) {
            return false;
        }

        $managerUids = $targetUser->getManagerUids();
        return in_array($managerId, $managerUids, true);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(string $userId): bool {
        return $this->groupManager->isAdmin($userId);
    }

    /**
     * Get all users that the given user manages
     * @return string[] Array of user IDs
     */
    public function getManagedUsers(string $managerId): array {
        $managedUsers = [];
        
        // Iterate through all users and check if this manager is in their manager list
        foreach ($this->userManager->search('') as $user) {
            $userId = $user->getUID();
            if ($this->isManagerOf($managerId, $userId)) {
                $managedUsers[] = $userId;
            }
        }
        
        return $managedUsers;
    }

    /**
     * Check if user can approve PTO requests
     * Returns true if user is a manager of anyone or is an admin
     */
    public function canApproveRequests(string $userId): bool {
        // Admins can approve any request
        if ($this->isAdmin($userId)) {
            return true;
        }

        // Check if user manages anyone
        $managedUsers = $this->getManagedUsers($userId);
        return count($managedUsers) > 0;
    }

    /**
     * Get managers for a user
     * @return string[] Array of manager user IDs
     */
    public function getManagersFor(string $userId): array {
        $user = $this->userManager->get($userId);
        if ($user === null) {
            return [];
        }

        return $user->getManagerUids();
    }

    /**
     * Set managers for a user
     * @param string $userId User to set managers for
     * @param string[] $managerIds Array of manager user IDs
     */
    public function setManagersFor(string $userId, array $managerIds): bool {
        $user = $this->userManager->get($userId);
        if ($user === null) {
            return false;
        }

        $user->setManagerUids($managerIds);
        return true;
    }
}
