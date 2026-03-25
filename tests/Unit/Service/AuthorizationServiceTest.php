<?php

declare(strict_types=1);

namespace OCA\PTO\Tests\Unit\Service;

use OCA\PTO\Service\AuthorizationService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceTest extends TestCase {
    private AuthorizationService $service;
    private $userManager;
    private $groupManager;

    protected function setUp(): void {
        parent::setUp();
        
        $this->userManager = $this->createMock(IUserManager::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        
        $this->service = new AuthorizationService(
            $this->userManager,
            $this->groupManager
        );
    }

    public function testIsAdminReturnsTrueForAdminUser(): void {
        $this->groupManager->expects($this->once())
            ->method('isAdmin')
            ->with('adminuser')
            ->willReturn(true);

        $result = $this->service->isAdmin('adminuser');
        
        $this->assertTrue($result);
    }

    public function testIsAdminReturnsFalseForRegularUser(): void {
        $this->groupManager->expects($this->once())
            ->method('isAdmin')
            ->with('regularuser')
            ->willReturn(false);

        $result = $this->service->isAdmin('regularuser');
        
        $this->assertFalse($result);
    }

    public function testIsManagerOfReturnsTrueWhenUserIsManager(): void {
        $targetUser = $this->createMock(IUser::class);
        $targetUser->expects($this->once())
            ->method('getManagerUids')
            ->willReturn(['manager1', 'manager2']);

        $this->userManager->expects($this->once())
            ->method('get')
            ->with('employee1')
            ->willReturn($targetUser);

        $result = $this->service->isManagerOf('manager1', 'employee1');
        
        $this->assertTrue($result);
    }

    public function testIsManagerOfReturnsFalseWhenUserIsNotManager(): void {
        $targetUser = $this->createMock(IUser::class);
        $targetUser->expects($this->once())
            ->method('getManagerUids')
            ->willReturn(['manager1', 'manager2']);

        $this->userManager->expects($this->once())
            ->method('get')
            ->with('employee1')
            ->willReturn($targetUser);

        $result = $this->service->isManagerOf('notmanager', 'employee1');
        
        $this->assertFalse($result);
    }

    public function testIsManagerOfReturnsFalseForNonexistentUser(): void {
        $this->userManager->expects($this->once())
            ->method('get')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->service->isManagerOf('manager1', 'nonexistent');
        
        $this->assertFalse($result);
    }

    public function testGetManagersForReturnsEmptyArrayForNonexistentUser(): void {
        $this->userManager->expects($this->once())
            ->method('get')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->service->getManagersFor('nonexistent');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetManagersForReturnsManagerUids(): void {
        $user = $this->createMock(IUser::class);
        $user->expects($this->once())
            ->method('getManagerUids')
            ->willReturn(['manager1', 'manager2']);

        $this->userManager->expects($this->once())
            ->method('get')
            ->with('employee1')
            ->willReturn($user);

        $result = $this->service->getManagersFor('employee1');
        
        $this->assertEquals(['manager1', 'manager2'], $result);
    }
}
