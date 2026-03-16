<?php
namespace Giant\BikeRegistration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Registration extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('giant_bike_registration', 'registration_id');
    }
}
