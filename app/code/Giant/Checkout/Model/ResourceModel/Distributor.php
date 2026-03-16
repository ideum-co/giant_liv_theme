<?php
declare(strict_types=1);

namespace Giant\Checkout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Distributor extends AbstractDb
{
    const TABLE_NAME   = 'giant_distributor';
    const ID_FIELD     = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD);
    }
}
