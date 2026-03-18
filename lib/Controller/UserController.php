<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IConfig;
use OCP\IRequest;

class UserController extends Controller {
    private IUserManager $userManager;
    private IGroupManager $groupManager;
    private IConfig $config;

    public function __construct(
        string $appName,
        IRequest $request,
        IUserManager $userManager,
        IGroupManager $groupManager,
        IConfig $config
    ) {
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->config = $config;
    }

    /**
     * Get all users with their manager status
     * 
     * @NoCSRFRequired
     */
    #[NoCSRFRequired]
    public function index(): JSONResponse {
        // TODO: Add admin permission check
        
        $users = [];
        $ptoManagers = $this->getPTOManagers();
        
        // Get all users
        foreach ($this->userManager->search('') as $user) {
            $userId = $user->getUID();
            $users[] = [
                'id' => $userId,
                'displayName' => $user->getDisplayName(),
                'email' => $user->getEMailAddress(),
                'isAdmin' => $this->groupManager->isAdmin($userId),
                'isManager' => in_array($userId, $ptoManagers),
            ];
        }
        
        return new JSONResponse(['users' => $users]);
    }

    /**
     * Update user manager status
     * 
     * @NoCSRFRequired
     */
    #[NoCSRFRequired]
    public function updateManager(string $userId): JSONResponse {
        // TODO: Add admin permission check
        
        $user = $this->userManager->get($userId);
        if ($user === null) {
            return new JSONResponse([
                'error' => 'User not found'
            ], Http::STATUS_NOT_FOUND);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $isManager = $data['isManager'] ?? false;
        
        $managers = $this->getPTOManagers();
        
        if ($isManager) {
            // Add to managers list
            if (!in_array($userId, $managers)) {
                $managers[] = $userId;
            }
        } else {
            // Remove from managers list
            $managers = array_values(array_filter($managers, fn($id) => $id !== $userId));
        }
        
        $this->setPTOManagers($managers);
        
        return new JSONResponse([
            'success' => true,
            'isManager' => $isManager,
        ]);
    }

    /**
     * Get list of PTO managers from config
     */
    private function getPTOManagers(): array {
        $managersJson = $this->config->getAppValue(
            Application::APP_ID,
            'pto_managers',
            '[]'
        );
        return json_decode($managersJson, true) ?: [];
    }

    /**
     * Set list of PTO managers in config
     */
    private function setPTOManagers(array $managers): void {
        $this->config->setAppValue(
            Application::APP_ID,
            'pto_managers',
            json_encode($managers)
        );
    }
}
