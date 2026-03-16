<?php
namespace Giant\Dealers\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Giant\Dealers\Model\ResourceModel\Dealer\CollectionFactory;

class Data extends Action
{
    protected $jsonFactory;
    protected $collectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('sort_order', 'ASC');

        $dealers = [];
        foreach ($collection as $dealer) {
            $dealers[] = [
                'id' => $dealer->getId(),
                'name' => $dealer->getData('name'),
                'logo' => $dealer->getData('logo'),
                'city' => $dealer->getData('city'),
                'address' => $dealer->getData('address'),
                'phones' => $dealer->getData('phones'),
                'email' => $dealer->getData('email'),
                'latitude' => (float)$dealer->getData('latitude'),
                'longitude' => (float)$dealer->getData('longitude'),
            ];
        }

        return $result->setData(['dealers' => $dealers]);
    }
}
