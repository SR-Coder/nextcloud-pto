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
 * @method string getRole()
 * @method void setRole(string $role)
 * @method string|null getManagerId()
 * @method void setManagerId(?string $managerId)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 */
class UserRole extends Entity implements JsonSerializable {
    protected $userId;
    protected $role;
    protected $managerId;
    protected $createdAt;

    public function __construct() {
        $this->addType('id', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'role' => $this->getRole(),
            'managerId' => $this->getManagerId(),
            'createdAt' => $this->getCreatedAt(),
        ];
    }
}
