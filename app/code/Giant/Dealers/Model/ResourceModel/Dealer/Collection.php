<?php
namespace Giant\Dealers\Model\ResourceModel\Dealer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'dealer_id';

    protected function _construct()
    {
        $this->_init(
            \Giant\Dealers\Model\Dealer::class,
            \Giant\Dealers\Model\ResourceModel\Dealer::class
        );
    }
}
