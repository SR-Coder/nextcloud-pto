<?php

declare(strict_types=1);

namespace OCA\PTO\Tests\Unit\Service;

use DateTime;
use OCA\PTO\Db\Policy;
use OCA\PTO\Db\PolicyMapper;
use OCA\PTO\Service\PolicyService;
use PHPUnit\Framework\TestCase;

class PolicyServiceTest extends TestCase {
    private PolicyMapper $mapper;
    private PolicyService $service;

    protected function setUp(): void {
        $this->mapper = $this->createMock(PolicyMapper::class);
        $this->service = new PolicyService($this->mapper);
    }

    public function testCalculateAccrualDaily(): void {
        $policy = new Policy();
        $policy->setType('accrual');
        $policy->setAccrualRate(8.0); // 8 hours per day
        $policy->setAccrualPeriod('daily');

        $lastAccrual = new DateTime('2026-01-01');
        $currentDate = new DateTime('2026-01-06'); // 5 days later

        $accrued = $this->service->calculateAccrual($policy, $lastAccrual, $currentDate);

        $this->assertEquals(40.0, $accrued); // 5 days * 8 hours
    }

    public function testCalculateAccrualWeekly(): void {
        $policy = new Policy();
        $policy->setType('accrual');
        $policy->setAccrualRate(40.0); // 40 hours per week
        $policy->setAccrualPeriod('weekly');

        $lastAccrual = new DateTime('2026-01-01');
        $currentDate = new DateTime('2026-01-15'); // 2 weeks later

        $accrued = $this->service->calculateAccrual($policy, $lastAccrual, $currentDate);

        $this->assertEquals(80.0, $accrued); // 2 weeks * 40 hours
    }

    public function testCalculateAccrualMonthly(): void {
        $policy = new Policy();
        $policy->setType('accrual');
        $policy->setAccrualRate(10.0); // 10 hours per month
        $policy->setAccrualPeriod('monthly');

        $lastAccrual = new DateTime('2026-01-01');
        $currentDate = new DateTime('2026-04-01'); // 3 months later

        $accrued = $this->service->calculateAccrual($policy, $lastAccrual, $currentDate);

        $this->assertEquals(30.0, $accrued); // 3 months * 10 hours
    }

    public function testCalculateAccrualYearly(): void {
        $policy = new Policy();
        $policy->setType('accrual');
        $policy->setAccrualRate(120.0); // 120 hours per year
        $policy->setAccrualPeriod('yearly');

        $lastAccrual = new DateTime('2024-01-01');
        $currentDate = new DateTime('2026-01-01'); // 2 years later

        $accrued = $this->service->calculateAccrual($policy, $lastAccrual, $currentDate);

        $this->assertEquals(240.0, $accrued); // 2 years * 120 hours
    }

    public function testCalculateAccrualNonAccrualType(): void {
        $policy = new Policy();
        $policy->setType('unlimited');

        $lastAccrual = new DateTime('2026-01-01');
        $currentDate = new DateTime('2026-01-06');

        $accrued = $this->service->calculateAccrual($policy, $lastAccrual, $currentDate);

        $this->assertEquals(0.0, $accrued); // Unlimited type doesn't accrue
    }

    public function testExceedsMaxBalance(): void {
        $policy = new Policy();
        $policy->setMaxBalance(100.0);

        $this->assertTrue($this->service->exceedsMaxBalance($policy, 90.0, 15.0));
        $this->assertFalse($this->service->exceedsMaxBalance($policy, 90.0, 5.0));
    }

    public function testExceedsMaxBalanceNoLimit(): void {
        $policy = new Policy();
        $policy->setMaxBalance(null); // No max balance

        $this->assertFalse($this->service->exceedsMaxBalance($policy, 1000.0, 500.0));
    }
}
