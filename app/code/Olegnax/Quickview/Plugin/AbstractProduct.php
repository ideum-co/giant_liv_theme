<?php

namespace Olegnax\Quickview\Plugin;

use Magento\Framework\App\Request\Http;

class AbstractProduct
{

    /**
     * @var  Http
     */
    protected $request;

    /**
     * ResultPage constructor.
     *
     * @param Http $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    /**
     * @param \Magento\Catalog\Block\Product\AbstractProduct $subject
     * @param $result
     *
     * @return bool
     */
    public function afterIsRedirectToCartEnabled(
        \Magento\Catalog\Block\Product\AbstractProduct $subject,
        $result
    ) {
        $requestUri = (string)$this->request->getRequestUri();
        if (strpos($requestUri, 'ox_quickview/catalog_product/view') !== false) {
            $result = false;
        }

        return $result;
    }
}
