<?php

namespace Olegnax\Quickview\Plugin;

use Magento\Checkout\Controller\Cart\Add;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Json\EncoderInterface;

class CartAdd
{

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
     * ResultPage constructor.
     *
     * @param Http $request
     * @param EncoderInterface $jsonEncoder
     */
    public function __construct(
        Http $request,
        EncoderInterface $jsonEncoder
    ) {
        $this->request = $request;
        $this->jsonEncoder = $jsonEncoder;
    }


    /**
     * @param Add $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute(
        Add $subject,
        $result
    ) {
        /** Fix for product redirects, ex. when quantity is out of stock */
        $refererUrl = (string)$this->request->getServer('HTTP_REFERER');
        if (strpos($refererUrl, 'ox_quickview/catalog_product/view') !== false) {
            return $subject->getResponse()->representJson($this->jsonEncoder->encode([]));
        }

        return $result;
    }


}
