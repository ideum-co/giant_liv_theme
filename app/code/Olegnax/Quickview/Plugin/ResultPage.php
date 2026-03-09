<?php

namespace Olegnax\Quickview\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;

class ResultPage
{

    /**
     * @var  Http
     */
    protected $request;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * ResultPage constructor.
     *
     * @param Http $request
     * @param Layout $layout
     */
    public function __construct(
        Http $request,
        Layout $layout
    ) {
        $this->request = $request;
        $this->layout = $layout;
    }

    /**
     * Adding the default catalog_product_view_type_ handles as well
     *
     * @param Page $subject
     * @param array $parameters
     * @param mixed $defaultHandle
     *
     * @return mixed
     */
    public function beforeAddPageLayoutHandles(
        Page $subject,
        array $parameters = [],
        $defaultHandle = null
    ) {
        if ($this->request->getFullActionName() == 'ox_quickview_catalog_product_view') {
            return [$parameters, 'catalog_product_view'];
        } else {
            return [$parameters, $defaultHandle];
        }
    }

}
