<?php
declare(strict_types=1);

namespace Giant\Checkout\Block;

use Giant\Checkout\Helper\Cart as CartHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\Quote\Item;

class Checkout extends Template
{
    public function __construct(
        Context                          $context,
        private readonly CartHelper      $cartHelper,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    // -------------------------------------------------------------------------
    // Cart / Quote helpers
    // -------------------------------------------------------------------------

    public function hasBicycleInCart(): bool
    {
        return $this->cartHelper->hasBicycleInCart();
    }

    public function showPriceWarning(): bool
    {
        return $this->cartHelper->showPriceWarning();
    }

    /** @return Item[] */
    public function getCartItems(): array
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote ? $quote->getAllVisibleItems() : [];
    }

    public function getQuoteGrandTotal(): float
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote ? (float) $quote->getGrandTotal() : 0.0;
    }

    public function getQuoteSubtotal(): float
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote ? (float) $quote->getSubtotal() : 0.0;
    }

    public function getQuoteCurrencyCode(): string
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote ? (string) $quote->getQuoteCurrencyCode() : '';
    }

    /**
     * Format a price value using the current store's currency.
     */
    public function formatPrice(float $price): string
    {
        return $this->_storeManager->getStore()
            ->getCurrentCurrency()
            ->formatPrecision($price, 2, [], true, false);
    }

    /**
     * Returns visible option labels for a cart item (color, size, etc.).
     *
     * @return string[]
     */
    public function getItemOptions(Item $item): array
    {
        $options = [];

        // Configurable product selected options
        if ($optionData = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct())) {
            if (!empty($optionData['attributes_info'])) {
                foreach ($optionData['attributes_info'] as $attr) {
                    $options[] = $attr['value'];
                }
            }
        }

        // Custom options
        if ($customOptions = $item->getProduct()->getCustomOptions()) {
            foreach ($customOptions as $key => $option) {
                if (strpos($key, 'option_') === 0) {
                    $options[] = $option->getValue();
                }
            }
        }

        return array_filter($options);
    }

    public function isItemBicycle(Item $item): bool
    {
        return $this->cartHelper->isItemBicycle($item);
    }

    public function getMaskedQuoteId(): string
    {
        if ($this->isLoggedIn()) {
            return '';
        }

        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return '';
        }

        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $quoteIdMask->getResource()->load($quoteIdMask, $quote->getId(), 'quote_id');

            if ($quoteIdMask->getMaskedId()) {
                return (string) $quoteIdMask->getMaskedId();
            }

            $quoteIdMask->setQuoteId((int) $quote->getId());
            $quoteIdMask->getResource()->save($quoteIdMask);
            return (string) $quoteIdMask->getMaskedId();
        } catch (\Exception $e) {
            return '';
        }
    }

    // -------------------------------------------------------------------------
    // Customer helpers
    // -------------------------------------------------------------------------

    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getForgotPasswordUrl(): string
    {
        return $this->getUrl('customer/account/forgotpassword');
    }

    public function getCustomerAccountUrl(): string
    {
        return $this->getUrl('customer/account');
    }

    // -------------------------------------------------------------------------
    // JS config for the frontend component
    // -------------------------------------------------------------------------

    public function getCheckoutConfig(): string
    {
        $config = [
            'hasBicycle'       => $this->hasBicycleInCart(),
            'isLoggedIn'       => $this->isLoggedIn(),
            'showPriceWarning' => $this->showPriceWarning(),
            'storeId'          => $this->cartHelper->getCurrentStoreId(),
            'currencyCode'     => $this->getQuoteCurrencyCode() ?: 'COP',
            'maskedCartId'     => $this->getMaskedQuoteId(),
            'urls' => [
                'distributors'    => $this->getUrl('giant/index/distributors'),
                'login'           => $this->getUrl('customer/ajax/login'),
                'guestAddress'    => $this->getUrl('giant/index/address'),
                'shippingMethods' => $this->getUrl('rest/V1/guest-carts'),
                'placeOrder'      => $this->getUrl('giant/index/placeOrder'),
                'cartDelete'      => $this->getUrl('checkout/cart/delete'),
                'applyCoupon'     => $this->getUrl('checkout/cart/couponPost'),
                'baseUrl'         => $this->getBaseUrl(),
            ],
        ];

        return json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}
