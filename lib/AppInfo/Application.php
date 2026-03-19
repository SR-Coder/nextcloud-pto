<?php

declare(strict_types=1);

namespace OCA\PTO\AppInfo;

use OCA\PTO\BackgroundJob\AccrualJob;
use OCA\PTO\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;

class Application extends App implements IBootstrap {
    public const APP_ID = 'pto';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // Register notification notifier
        $context->registerNotifierService(Notifier::class);
    }

    public function boot(IBootContext $context): void {
        $container = $context->getServerContainer();
        
        // Register background jobs
        /** @var IJobList $jobList */
        $jobList = $container->get(IJobList::class);
        
        // Add accrual job if not already registered
        if (!$jobList->has(AccrualJob::class, null)) {
            $jobList->add(AccrualJob::class);
        }
    }
}
