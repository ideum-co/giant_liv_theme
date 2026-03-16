<?php
namespace Giant\Dealers\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Giant\Dealers\Model\ResourceModel\Dealer\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class DealerList extends Template
{
    protected $collectionFactory;
    protected $storeManager;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    public function getDealers()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('sort_order', 'ASC');
        return $collection;
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getDealersJson()
    {
        $dealers = [];
        foreach ($this->getDealers() as $dealer) {
            $dealers[] = [
                'id' => $dealer->getId(),
                'name' => $dealer->getData('name'),
                'city' => $dealer->getData('city'),
                'address' => $dealer->getData('address'),
                'phones' => $dealer->getData('phones'),
                'email' => $dealer->getData('email'),
                'latitude' => (float)$dealer->getData('latitude'),
                'longitude' => (float)$dealer->getData('longitude'),
            ];
        }
        return json_encode($dealers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
