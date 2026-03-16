<?php
namespace Giant\BikeRegistration\Model;

use Magento\Framework\Model\AbstractModel;

class Registration extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Giant\BikeRegistration\Model\ResourceModel\Registration::class);
    }
}
