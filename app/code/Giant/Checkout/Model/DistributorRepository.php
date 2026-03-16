<?php
declare(strict_types=1);

namespace Giant\Checkout\Model;

use Giant\Checkout\Api\Data\DistributorInterface;
use Giant\Checkout\Api\DistributorRepositoryInterface;
use Giant\Checkout\Model\ResourceModel\Distributor as DistributorResource;
use Giant\Checkout\Model\ResourceModel\Distributor\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class DistributorRepository implements DistributorRepositoryInterface
{
    public function __construct(
        private readonly DistributorFactory  $distributorFactory,
        private readonly DistributorResource $resource,
        private readonly CollectionFactory   $collectionFactory
    ) {}

    public function getById(int $id): DistributorInterface
    {
        $distributor = $this->distributorFactory->create();
        $this->resource->load($distributor, $id);

        if (!$distributor->getId()) {
            throw new NoSuchEntityException(__('Distributor with ID "%1" does not exist.', $id));
        }

        return $distributor;
    }

    public function save(DistributorInterface $distributor): DistributorInterface
    {
        try {
            $this->resource->save($distributor);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $distributor;
    }

    public function delete(DistributorInterface $distributor): bool
    {
        try {
            $this->resource->delete($distributor);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    public function getActiveList(?int $storeId = null): array
    {
        $collection = $this->collectionFactory->create()
            ->addActiveFilter()
            ->addDefaultOrder();

        if ($storeId !== null) {
            $collection->addStoreFilter($storeId);
        }

        return $collection->getItems();
    }
}
