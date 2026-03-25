<?php

declare(strict_types=1);

namespace OCA\PTO\Controller;

use OCA\PTO\Service\CalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class CalendarSettingsController extends Controller {
    private CalendarService $calendarService;
    private string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        CalendarService $calendarService,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->calendarService = $calendarService;
        $this->userId = $userId;
    }

    /**
     * Get available calendars for the current user
     * 
     * @NoAdminRequired
     */
    public function getCalendars(): DataResponse {
        try {
            $calendars = $this->calendarService->getCalendarsForUser($this->userId);
            
            return new DataResponse([
                'calendars' => $calendars,
                'selectedUri' => $this->calendarService->getPTOCalendarUri(),
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => 'Failed to load calendars'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Save the selected calendar for PTO events
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function setCalendar(string $uri = null): DataResponse {
        try {
            $this->calendarService->setPTOCalendarUri($uri);
            
            return new DataResponse([
                'success' => true,
                'uri' => $uri,
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => 'Failed to save calendar setting'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
