<?php
namespace Giant\Dealers\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Dealer extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('giant_dealers', 'dealer_id');
    }
}
