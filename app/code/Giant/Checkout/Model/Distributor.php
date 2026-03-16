<?php
declare(strict_types=1);

namespace Giant\Checkout\Model;

use Giant\Checkout\Api\Data\DistributorInterface;
use Magento\Framework\Model\AbstractModel;
use Giant\Checkout\Model\ResourceModel\Distributor as DistributorResource;

class Distributor extends AbstractModel implements DistributorInterface
{
    protected $_eventPrefix = 'giant_distributor';

    protected function _construct(): void
    {
        $this->_init(DistributorResource::class);
    }

    public function getId(): ?int
    {
        $id = $this->getData(self::ENTITY_ID);
        return $id !== null ? (int) $id : null;
    }

    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getAddress(): ?string
    {
        return $this->getData(self::ADDRESS);
    }

    public function setAddress(?string $address): self
    {
        return $this->setData(self::ADDRESS, $address);
    }

    public function getCity(): ?string
    {
        return $this->getData(self::CITY);
    }

    public function setCity(?string $city): self
    {
        return $this->setData(self::CITY, $city);
    }

    public function getDepartment(): ?string
    {
        return $this->getData(self::DEPARTMENT);
    }

    public function setDepartment(?string $department): self
    {
        return $this->setData(self::DEPARTMENT, $department);
    }

    public function getPhone(): ?string
    {
        return $this->getData(self::PHONE);
    }

    public function setPhone(?string $phone): self
    {
        return $this->setData(self::PHONE, $phone);
    }

    public function getEmail(): ?string
    {
        return $this->getData(self::EMAIL);
    }

    public function setEmail(?string $email): self
    {
        return $this->setData(self::EMAIL, $email);
    }

    public function getIsActive(): int
    {
        return (int) $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(int $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): self
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getStoreIds(): ?string
    {
        return $this->getData(self::STORE_IDS);
    }

    public function setStoreIds(?string $storeIds): self
    {
        return $this->setData(self::STORE_IDS, $storeIds);
    }
}
