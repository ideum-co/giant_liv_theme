<?php
namespace IDeum\BodyClass\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class BodyClass extends Template
{
    public function getBodyClass()
    {
        $store = $this->_storeManager->getStore();
        $storeCode = $store->getCode();

        if ($storeCode == 'default') {
            return 'giant';
        } elseif ($storeCode == 'liv_store_view') {
            return 'liv';
        }
        return '';
    }
}
