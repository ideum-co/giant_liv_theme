<?php
declare(strict_types=1);

namespace Giant\Checkout\Api;

use Giant\Checkout\Api\Data\DistributorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface DistributorRepositoryInterface
{
    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): DistributorInterface;

    /**
     * @throws CouldNotSaveException
     */
    public function save(DistributorInterface $distributor): DistributorInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(DistributorInterface $distributor): bool;

    /**
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): bool;

    /**
     * Returns active distributors, optionally filtered by store ID.
     *
     * @return DistributorInterface[]
     */
    public function getActiveList(?int $storeId = null): array;
}
