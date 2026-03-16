<?php
declare(strict_types=1);

namespace Giant\Checkout\Block\Product;

use Giant\Checkout\Helper\Cart as CartHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class BicycleInfo extends Template
{
    public function __construct(
        Context $context,
        private readonly CartHelper $cartHelper,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isBicycle(): bool
    {
        $product = $this->getProduct();
        if (!$product) {
            return false;
        }

        $bicycleCategoryIds = $this->cartHelper->getBicycleCategoryIds();
        $attrCode  = $this->cartHelper->getBicycleAttributeCode();
        $attrValue = $this->cartHelper->getBicycleAttributeValue();

        if ($attrCode && $attrValue !== null) {
            $val = $product->getData($attrCode);
            if ($val !== null && (string)$val === (string)$attrValue) {
                return true;
            }
        }

        if (!empty($bicycleCategoryIds)) {
            $productCategoryIds = $product->getCategoryIds();
            if (array_intersect($productCategoryIds, $bicycleCategoryIds)) {
                return true;
            }
        }

        return false;
    }

    public function getProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }
}
