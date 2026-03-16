<?php
declare(strict_types=1);

namespace Giant\Checkout\Model\ResourceModel\Distributor;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Giant\Checkout\Model\Distributor;
use Giant\Checkout\Model\ResourceModel\Distributor as DistributorResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(Distributor::class, DistributorResource::class);
    }

    /**
     * Filter only active distributors.
     */
    public function addActiveFilter(): self
    {
        return $this->addFieldToFilter('is_active', 1);
    }

    /**
     * Filter by store ID (also includes distributors with no store restriction).
     */
    public function addStoreFilter(int $storeId): self
    {
        $this->addFieldToFilter(
            ['store_ids', 'store_ids', 'store_ids', 'store_ids'],
            [
                ['null' => true],
                ['eq' => (string) $storeId],
                ['like' => $storeId . ',%'],
                ['like' => '%,' . $storeId . '%'],
            ]
        );
        return $this;
    }

    /**
     * Apply default sort order.
     */
    public function addDefaultOrder(): self
    {
        return $this->setOrder('sort_order', 'ASC')->setOrder('name', 'ASC');
    }
}
