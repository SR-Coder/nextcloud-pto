<?php

declare(strict_types=1);

namespace OCA\PTO\Service;

use OCP\Calendar\IManager;
use OCP\Calendar\ICreateFromString;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class CalendarService {
    private IManager $calendarManager;
    private IConfig $config;
    private IUserManager $userManager;
    private LoggerInterface $logger;

    public function __construct(
        IManager $calendarManager,
        IConfig $config,
        IUserManager $userManager,
        LoggerInterface $logger
    ) {
        $this->calendarManager = $calendarManager;
        $this->config = $config;
        $this->userManager = $userManager;
        $this->logger = $logger;
    }

    /**
     * Get all available calendars for a user
     */
    public function getCalendarsForUser(string $userId): array {
        $principal = 'principals/users/' . $userId;
        $calendars = $this->calendarManager->getCalendarsForPrincipal($principal);

        $result = [];
        foreach ($calendars as $calendar) {
            $result[] = [
                'uri' => $calendar->getUri(),
                'displayName' => $calendar->getDisplayName(),
                'writable' => $calendar instanceof ICreateFromString,
            ];
        }

        return $result;
    }

    /**
     * Get the configured PTO calendar URI from settings
     */
    public function getPTOCalendarUri(): ?string {
        return $this->config->getAppValue('pto', 'calendar_uri', null);
    }

    /**
     * Set the PTO calendar URI in settings
     */
    public function setPTOCalendarUri(?string $uri): void {
        if ($uri === null) {
            $this->config->deleteAppValue('pto', 'calendar_uri');
        } else {
            $this->config->setAppValue('pto', 'calendar_uri', $uri);
        }
    }

    /**
     * Create a PTO event in the calendar when a request is approved
     */
    public function createPTOEvent(
        string $userId,
        string $leaveType,
        \DateTime $startDate,
        \DateTime $endDate,
        float $hours,
        ?string $notes = null
    ): bool {
        try {
            $calendarUri = $this->getPTOCalendarUri();
            if ($calendarUri === null) {
                // No calendar configured, skip event creation
                return false;
            }

            $principal = 'principals/users/' . $userId;
            $calendars = $this->calendarManager->getCalendarsForPrincipal($principal, [$calendarUri]);

            if (empty($calendars)) {
                $this->logger->warning('PTO calendar not found for user', ['userId' => $userId, 'calendarUri' => $calendarUri]);
                return false;
            }

            $calendar = $calendars[0];
            
            if (!($calendar instanceof ICreateFromString)) {
                $this->logger->warning('PTO calendar is not writable', ['userId' => $userId, 'calendarUri' => $calendarUri]);
                return false;
            }

            // Get user's display name
            $user = $this->userManager->get($userId);
            $displayName = $user ? $user->getDisplayName() : $userId;

            // Build the event
            $summary = sprintf('[PTO] %s - %s', $displayName, $leaveType);
            $description = sprintf(
                "Time Off Request\n\nDuration: %.1f hours\n%s",
                $hours,
                $notes ? "Notes: {$notes}" : ''
            );

            // Convert to DateTimeImmutable with proper timezone
            $start = \DateTimeImmutable::createFromMutable($startDate);
            $end = \DateTimeImmutable::createFromMutable($endDate);
            
            // Make it an all-day event
            $start = $start->setTime(0, 0, 0);
            // End date should be the day AFTER the last day (iCal convention)
            $end = $end->setTime(0, 0, 0)->add(new \DateInterval('P1D'));

            $builder = $this->calendarManager->createEventBuilder()
                ->setStartDate($start)
                ->setEndDate($end)
                ->setSummary($summary)
                ->setDescription($description)
                ->setAllDay(true);

            // Generate unique filename
            $uuid = bin2hex(random_bytes(16));
            $filename = sprintf('pto-%s.ics', $uuid);

            // Create the event in the calendar
            $ics = $builder->toIcs();
            $calendar->createFromString($filename, $ics);

            $this->logger->info('Created PTO calendar event', [
                'userId' => $userId,
                'leaveType' => $leaveType,
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create PTO calendar event', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
