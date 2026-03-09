<?php

namespace Olegnax\Quickview\Plugin;

use Closure;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class ListProduct
{

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param UrlInterface $urlInterface
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlInterface,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->urlInterface = $urlInterface;
        $this->scopeConfig = $scopeConfig;
    }

    public function aroundGetProductDetailsHtml(
        \Magento\Catalog\Block\Product\ListProduct $subject,
        Closure $proceed,
        Product $product
    ) {
        $result = $proceed($product);
        $isEnabled = $this->getConfig('olegnax_quickview/general/enable');
        if ($isEnabled) {
            $productUrl = $this->urlInterface->getUrl('ox_quickview/catalog_product/view',
                array('id' => $product->getId()));
            return $result . '<a class="ox-quickview-button" data-quickview-url="' . $productUrl . '" href="#"><span>' . __("Quickview") . '</span></a>';
        }

        return $result;
    }

    public function getConfig($path, $storeCode = null)
    {
        return $this->getSystemValue($path, $storeCode);
    }

    public function getSystemValue($path, $storeCode = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
    }

}
