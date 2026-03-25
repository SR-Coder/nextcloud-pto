<?php

declare(strict_types=1);

namespace OCA\PTO\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getType()
 * @method void setType(string $type)
 * @method float|null getAccrualRate()
 * @method void setAccrualRate(?float $accrualRate)
 * @method string|null getAccrualPeriod()
 * @method void setAccrualPeriod(?string $accrualPeriod)
 * @method float|null getMaxBalance()
 * @method void setMaxBalance(?float $maxBalance)
 * @method float|null getFixedAnnualHours()
 * @method void setFixedAnnualHours(?float $fixedAnnualHours)
 * @method string|null getResetDate()
 * @method void setResetDate(?string $resetDate)
 * @method bool getEnabled()
 * @method void setEnabled(bool $enabled)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 */
class Policy extends Entity implements JsonSerializable {
    protected $name;
    protected $type;
    protected $accrualRate;
    protected $accrualPeriod;
    protected $maxBalance;
    protected $fixedAnnualHours;
    protected $resetDate;
    protected $enabled;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('enabled', 'boolean');
        $this->addType('accrualRate', 'float');
        $this->addType('maxBalance', 'float');
        $this->addType('fixedAnnualHours', 'float');
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'accrualRate' => $this->getAccrualRate(),
            'accrualPeriod' => $this->getAccrualPeriod(),
            'maxBalance' => $this->getMaxBalance(),
            'fixedAnnualHours' => $this->getFixedAnnualHours(),
            'resetDate' => $this->getResetDate(),
            'enabled' => $this->getEnabled(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
        ];
    }
}
