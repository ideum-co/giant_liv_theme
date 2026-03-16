<?php
namespace Giant\Dealers\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;

class NewAction extends Action
{
    const ADMIN_RESOURCE = 'Giant_Dealers::dealers';

    public function execute()
    {
        return $this->_forward('edit');
    }
}
