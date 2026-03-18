<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method int getRequestId()
 * @method void setRequestId(int $requestId)
 * @method string getManagerId()
 * @method void setManagerId(string $managerId)
 * @method string getAction()
 * @method void setAction(string $action)
 * @method string|null getComments()
 * @method void setComments(?string $comments)
 * @method string getActedAt()
 * @method void setActedAt(string $actedAt)
 */
class Approval extends Entity implements JsonSerializable {
    protected $requestId;
    protected $managerId;
    protected $action;
    protected $comments;
    protected $actedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('requestId', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'requestId' => $this->getRequestId(),
            'managerId' => $this->getManagerId(),
            'action' => $this->getAction(),
            'comments' => $this->getComments(),
            'actedAt' => $this->getActedAt(),
        ];
    }
}
