<?php
namespace Giant\Dealers\Model;

use Giant\Dealers\Model\ResourceModel\Dealer as DealerResource;
use Giant\Dealers\Model\ResourceModel\Dealer\CollectionFactory;

class DealerRepository
{
    protected $resource;
    protected $dealerFactory;
    protected $collectionFactory;

    public function __construct(
        DealerResource $resource,
        DealerFactory $dealerFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->dealerFactory = $dealerFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function save(Dealer $dealer)
    {
        $this->resource->save($dealer);
        return $dealer;
    }

    public function getById($dealerId)
    {
        $dealer = $this->dealerFactory->create();
        $this->resource->load($dealer, $dealerId);
        return $dealer;
    }

    public function delete(Dealer $dealer)
    {
        $this->resource->delete($dealer);
    }

    public function getActiveDealers()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('sort_order', 'ASC');
        return $collection;
    }
}
