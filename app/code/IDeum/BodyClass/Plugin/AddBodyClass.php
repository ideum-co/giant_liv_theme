<?php
namespace IDeum\BodyClass\Plugin;

use Magento\Framework\View\Layout\Interceptor;

class AddBodyClass
{
    protected $storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    public function afterGetBodyClass(Interceptor $subject, $result)
    {
        $store = $this->storeManager->getStore();
        $storeCode = $store->getCode();

        if ($storeCode == 'default') {
            $result .= ' giant';
        } elseif ($storeCode == 'liv_store_view') {
            $result .= ' liv';
        }

        return $result;
    }
}
