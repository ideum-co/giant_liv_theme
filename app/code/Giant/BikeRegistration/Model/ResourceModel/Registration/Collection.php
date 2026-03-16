<?php
namespace Giant\BikeRegistration\Model\ResourceModel\Registration;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'registration_id';

    protected function _construct()
    {
        $this->_init(
            \Giant\BikeRegistration\Model\Registration::class,
            \Giant\BikeRegistration\Model\ResourceModel\Registration::class
        );
    }
}
