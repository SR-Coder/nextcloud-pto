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
     * Escape text for iCalendar format per RFC 5545
     * Escapes: backslash, semicolon, comma, and converts newlines to \n
     */
    private function escapeIcalText(string $text): string {
        // Escape backslashes first
        $text = str_replace('\\', '\\\\', $text);
        // Escape semicolons
        $text = str_replace(';', '\\;', $text);
        // Escape commas
        $text = str_replace(',', '\\,', $text);
        // Convert newlines to literal \n
        $text = str_replace("\r\n", "\\n", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace("\r", "\\n", $text);
        return $text;
    }

    /**
     * Find a calendar by URI across all users
     */
    private function findCalendarByUri(string $targetUri): ?ICreateFromString {
        // Get all users
        $users = $this->userManager->search('');
        
        foreach ($users as $user) {
            $principal = 'principals/users/' . $user->getUID();
            $calendars = $this->calendarManager->getCalendarsForPrincipal($principal);
            
            foreach ($calendars as $calendar) {
                if ($calendar->getUri() === $targetUri && $calendar instanceof ICreateFromString) {
                    return $calendar;
                }
            }
        }
        
        return null;
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
     * Supports both NC28+ (event builder) and NC27 (manual iCal)
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

            // Find the calendar across all users
            // The calendar owner might not be the PTO requester
            $calendar = $this->findCalendarByUri($calendarUri);

            if ($calendar === null) {
                $this->logger->warning('PTO calendar not found', ['calendarUri' => $calendarUri]);
                return false;
            }
            
            if (!($calendar instanceof ICreateFromString)) {
                $this->logger->warning('PTO calendar is not writable', ['userId' => $userId, 'calendarUri' => $calendarUri]);
                return false;
            }

            // Get user's display name
            $user = $this->userManager->get($userId);
            $displayName = $user ? $user->getDisplayName() : $userId;

            // Try modern event builder (NC28+) first, fall back to manual iCal (NC27)
            if (method_exists($this->calendarManager, 'createEventBuilder')) {
                return $this->createEventWithBuilder($calendar, $userId, $displayName, $leaveType, $startDate, $endDate, $hours, $notes);
            } else {
                return $this->createEventManual($calendar, $userId, $displayName, $leaveType, $startDate, $endDate, $hours, $notes);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to create PTO calendar event', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create event using modern event builder API (NC28+)
     */
    private function createEventWithBuilder(
        ICreateFromString $calendar,
        string $userId,
        string $displayName,
        string $leaveType,
        \DateTime $startDate,
        \DateTime $endDate,
        float $hours,
        ?string $notes
    ): bool {
        // Build the event
        $summary = sprintf('[PTO] %s - %s', $displayName, $leaveType);
        $description = sprintf(
            "Time Off Request\n\nDuration: %.1f hours\n%s",
            $hours,
            $notes ? "Notes: {$notes}" : ''
        );

        // Convert to DateTimeImmutable for event builder
        $start = \DateTimeImmutable::createFromMutable($startDate)->setTime(0, 0, 0);
        // End date should be the day AFTER the last day (iCal convention)
        $end = \DateTimeImmutable::createFromMutable($endDate)->setTime(0, 0, 0)->add(new \DateInterval('P1D'));

        $builder = $this->calendarManager->createEventBuilder()
            ->setStartDate($start)
            ->setEndDate($end)
            ->setSummary($summary)
            ->setDescription($description)
            ->setAllDay(true);

        // Create in calendar
        $builder->createInCalendar($calendar);

        $this->logger->info('Created PTO calendar event (event builder)', [
            'userId' => $userId,
            'leaveType' => $leaveType,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);

        return true;
    }

    /**
     * Create event using manual iCal generation (NC27 and below)
     * RFC 5545 compliant
     */
    private function createEventManual(
        ICreateFromString $calendar,
        string $userId,
        string $displayName,
        string $leaveType,
        \DateTime $startDate,
        \DateTime $endDate,
        float $hours,
        ?string $notes
    ): bool {
        // Build the event (RFC 5545 compliant text escaping)
        $summary = $this->escapeIcalText(sprintf('[PTO] %s - %s', $displayName, $leaveType));
        $description = $this->escapeIcalText(sprintf(
            "Time Off Request\n\nDuration: %.1f hours\n%s",
            $hours,
            $notes ? "Notes: {$notes}" : ''
        ));

        // Format dates for all-day events (YYYYMMDD)
        $startFormatted = $startDate->format('Ymd');
        // End date should be the day AFTER the last day (iCal convention)
        $endDatePlusOne = (clone $endDate)->add(new \DateInterval('P1D'));
        $endFormatted = $endDatePlusOne->format('Ymd');
        
        // Generate unique UID
        $uuid = bin2hex(random_bytes(16));
        $uid = sprintf('%s@nextcloud', $uuid);
        $timestamp = (new \DateTime())->format('Ymd\THis\Z');

        // Build iCal content manually per RFC 5545
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Nextcloud PTO//EN\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$timestamp}\r\n";
        $ics .= "DTSTART;VALUE=DATE:{$startFormatted}\r\n";
        $ics .= "DTEND;VALUE=DATE:{$endFormatted}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "TRANSP:OPAQUE\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        // Generate unique filename
        $filename = sprintf('pto-%s.ics', $uuid);

        // Create the event in the calendar
        $calendar->createFromString($filename, $ics);

        $this->logger->info('Created PTO calendar event (manual iCal)', [
            'userId' => $userId,
            'leaveType' => $leaveType,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);

        return true;
    }
}
