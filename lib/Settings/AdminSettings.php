<?php

declare(strict_types=1);

namespace OCA\PTO\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
    private IL10N $l;
    private IConfig $config;

    public function __construct(IConfig $config, IL10N $l) {
        $this->config = $config;
        $this->l = $l;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        return new TemplateResponse('pto', 'settings-admin', [], '');
    }

    public function getSection(): string {
        return 'pto'; // ID from AdminSection
    }

    /**
     * Priority within the section (0-100, lower = higher priority)
     */
    public function getPriority(): int {
        return 10;
    }
}
