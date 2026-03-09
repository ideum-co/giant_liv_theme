<?php

namespace Olegnax\Quickview\Plugin;

use Closure;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Store\Model\ScopeInterface;

class BlockProductViewGalleryMagnifier
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var  Http
     */
    protected $request;

    /**
     *
     * @var  EncoderInterface
     */
    protected $jsonEncoder;

    /**
     *
     * @var  DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * ResultPage constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Gallery $subject
     * @param Closure $proceed
     * @param string $name
     * @param string|null $module
     *
     * @return string|false
     */
    public function aroundGetVar(
        Gallery $subject,
        Closure $proceed,
        $name,
        $module = null
    ) {
        $result = $proceed($name, $module);
        $isEnabled = $this->getSystemValue('olegnax_quickview/general/enable');

        if (!$isEnabled || $this->request->getFullActionName() != 'ox_quickview_catalog_product_view') {
            return $result;
        }

        switch ($name) {
            case "gallery/navdir" :
                $result = 'horizontal';
                break;
            /* Disable the image fullscreen on quickview*/
            case "gallery/allowfullscreen" :
                $result = false;
                break;
        }

        return $result;
    }

    public function getSystemValue($path, $storeCode = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
    }


}
