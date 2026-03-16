<?php
declare(strict_types=1);

namespace Giant\Checkout\Helper;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Helpers related to cart analysis (e.g. detect bicycles).
 */
class Cart extends AbstractHelper
{
    /**
     * Category IDs that represent "Bicycle" products.
     * Configure these in System > Configuration or adjust as needed.
     */
    const XML_PATH_BICYCLE_CATEGORY_IDS = 'giant_checkout/general/bicycle_category_ids';

    /**
     * Product attribute code that marks a product as a bicycle.
     * Leave empty to rely only on category.
     */
    const XML_PATH_BICYCLE_ATTRIBUTE    = 'giant_checkout/general/bicycle_attribute_code';
    const XML_PATH_BICYCLE_ATTR_VALUE   = 'giant_checkout/general/bicycle_attribute_value';

    /**
     * Show price-varies warning (for Mexico stores).
     */
    const XML_PATH_SHOW_PRICE_WARNING   = 'giant_checkout/general/show_price_warning';

    public function __construct(
        Context $context,
        private readonly CheckoutSession       $checkoutSession,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * Returns true if any item in the current quote is a bicycle.
     */
    public function hasBicycleInCart(): bool
    {
        $quote = $this->checkoutSession->getQuote();

        if (!$quote || !$quote->hasItems()) {
            return false;
        }

        $bicycleCategoryIds = $this->getBicycleCategoryIds();
        $attrCode           = $this->getBicycleAttributeCode();
        $attrValue          = $this->getBicycleAttributeValue();

        foreach ($quote->getAllVisibleItems() as $item) {
            /** @var Product $product */
            $product = $item->getProduct();

            // Check by custom attribute
            if ($attrCode && $attrValue !== null) {
                $val = $product->getData($attrCode);
                if ($val !== null && (string)$val === (string)$attrValue) {
                    return true;
                }
            }

            // Check by category
            if (!empty($bicycleCategoryIds)) {
                $productCategoryIds = $product->getCategoryIds();
                if (array_intersect($productCategoryIds, $bicycleCategoryIds)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return int[]
     */
    public function getBicycleCategoryIds(): array
    {
        $raw = $this->scopeConfig->getValue(
            self::XML_PATH_BICYCLE_CATEGORY_IDS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$raw) {
            return [];
        }

        return array_filter(array_map('intval', explode(',', $raw)));
    }

    public function getBicycleAttributeCode(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_BICYCLE_ATTRIBUTE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBicycleAttributeValue(): ?string
    {
        $val = $this->scopeConfig->getValue(
            self::XML_PATH_BICYCLE_ATTR_VALUE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $val !== null ? (string) $val : null;
    }

    public function showPriceWarning(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_SHOW_PRICE_WARNING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isItemBicycle(\Magento\Quote\Model\Quote\Item $item): bool
    {
        $product = $item->getProduct();

        $attrCode  = $this->getBicycleAttributeCode();
        $attrValue = $this->getBicycleAttributeValue();

        if ($attrCode && $attrValue !== null) {
            $val = $product->getData($attrCode);
            if ($val !== null && (string)$val === (string)$attrValue) {
                return true;
            }
        }

        $bicycleCategoryIds = $this->getBicycleCategoryIds();
        if (!empty($bicycleCategoryIds)) {
            $productCategoryIds = $product->getCategoryIds();
            if (array_intersect($productCategoryIds, $bicycleCategoryIds)) {
                return true;
            }
        }

        return false;
    }

    public function getCurrentStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}
