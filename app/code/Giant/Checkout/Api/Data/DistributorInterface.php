<?php
declare(strict_types=1);

namespace Giant\Checkout\Api\Data;

interface DistributorInterface
{
    const ENTITY_ID  = 'entity_id';
    const NAME       = 'name';
    const ADDRESS    = 'address';
    const CITY       = 'city';
    const DEPARTMENT = 'department';
    const PHONE      = 'phone';
    const EMAIL      = 'email';
    const IS_ACTIVE  = 'is_active';
    const SORT_ORDER = 'sort_order';
    const STORE_IDS  = 'store_ids';

    public function getId(): ?int;
    public function getName(): string;
    public function setName(string $name): self;
    public function getAddress(): ?string;
    public function setAddress(?string $address): self;
    public function getCity(): ?string;
    public function setCity(?string $city): self;
    public function getDepartment(): ?string;
    public function setDepartment(?string $department): self;
    public function getPhone(): ?string;
    public function setPhone(?string $phone): self;
    public function getEmail(): ?string;
    public function setEmail(?string $email): self;
    public function getIsActive(): int;
    public function setIsActive(int $isActive): self;
    public function getSortOrder(): int;
    public function setSortOrder(int $sortOrder): self;
    public function getStoreIds(): ?string;
    public function setStoreIds(?string $storeIds): self;
}
