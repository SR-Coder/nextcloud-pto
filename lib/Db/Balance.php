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
 * @method float getBalance()
 * @method void setBalance(float $balance)
 * @method float getAccruedThisPeriod()
 * @method void setAccruedThisPeriod(float $accrued)
 * @method float getUsedThisYear()
 * @method void setUsedThisYear(float $used)
 * @method string|null getLastAccrualDate()
 * @method void setLastAccrualDate(?string $date)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 */
class Balance extends Entity implements JsonSerializable {
    protected $userId;
    protected $policyId;
    protected $balance;
    protected $accruedThisPeriod;
    protected $usedThisYear;
    protected $lastAccrualDate;
    protected $updatedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('policyId', 'integer');
        $this->addType('balance', 'float');
        $this->addType('accruedThisPeriod', 'float');
        $this->addType('usedThisYear', 'float');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'policyId' => $this->getPolicyId(),
            'balance' => $this->getBalance(),
            'accruedThisPeriod' => $this->getAccruedThisPeriod(),
            'usedThisYear' => $this->getUsedThisYear(),
            'lastAccrualDate' => $this->getLastAccrualDate(),
            'updatedAt' => $this->getUpdatedAt(),
        ];
    }
}
