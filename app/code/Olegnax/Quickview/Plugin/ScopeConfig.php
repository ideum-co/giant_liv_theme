<?php

namespace Olegnax\Quickview\Plugin;

use Closure;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;

class ScopeConfig
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
     * @param ScopeConfigInterface $subject
     * @param Closure $proceed
     * @param $path
     * @param $scopeType
     * @param null $scopeCode
     *
     * @return string
     */
    public function aroundGetValue(
        ScopeConfigInterface $subject,
        Closure $proceed,
        $path = '',
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        $result = $proceed($path, $scopeType, $scopeCode);

        if (($path == 'checkout/cart/redirect_to_cart')) {
            $refererUrl = (string)$this->request->getServer('HTTP_REFERER');
            if (strpos($refererUrl, 'ox_quickview/catalog_product/view') !== false) {
                $result = false;
            }
        }
        return $result;
    }

}
