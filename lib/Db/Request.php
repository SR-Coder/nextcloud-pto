<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getPolicyId()
 * @method void setPolicyId(int $policyId)
 * @method string getLeaveType()
 * @method void setLeaveType(string $leaveType)
 * @method string getStartDate()
 * @method void setStartDate(string $startDate)
 * @method string getEndDate()
 * @method void setEndDate(string $endDate)
 * @method float getHours()
 * @method void setHours(float $hours)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method string|null getNotes()
 * @method void setNotes(?string $notes)
 * @method string getSubmittedBy()
 * @method void setSubmittedBy(string $submittedBy)
 * @method string|null getCalendarEventId()
 * @method void setCalendarEventId(?string $eventId)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 */
class Request extends Entity implements JsonSerializable {
    protected $userId;
    protected $policyId;
    protected $leaveType;
    protected $startDate;
    protected $endDate;
    protected $hours;
    protected $status;
    protected $notes;
    protected $submittedBy;
    protected $calendarEventId;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('policyId', 'integer');
        $this->addType('hours', 'float');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'policyId' => $this->getPolicyId(),
            'leaveType' => $this->getLeaveType(),
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
            'hours' => $this->getHours(),
            'status' => $this->getStatus(),
            'notes' => $this->getNotes(),
            'submittedBy' => $this->getSubmittedBy(),
            'calendarEventId' => $this->getCalendarEventId(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
        ];
    }
}
