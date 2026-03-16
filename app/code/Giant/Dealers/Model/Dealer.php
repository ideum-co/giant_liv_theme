<?php
namespace Giant\Dealers\Model;

use Magento\Framework\Model\AbstractModel;

class Dealer extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Giant\Dealers\Model\ResourceModel\Dealer::class);
    }
}
