<?php

namespace Nwdthemes\Revslider\Plugin\Cms\Controller\Adminhtml\Wysiwyg\Images;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Cms\Helper\Wysiwyg\Images as ImagesHelper;
use Magento\Framework\Controller\Result\JsonFactory;

class OnInsertPlugin
{
    /**
     * @var CatalogHelper
     */
    protected $_catalogHelper;

    /**
     * @var ImagesHelper
     */
    protected $_imagesHelper;

    /**
     * @var JsonFactory
     */
    protected $_jsonFactory;

    /**
     * @param CatalogHelper $catalogHelper
     * @param ImagesHelper $imagesHelper
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        CatalogHelper $catalogHelper,
        ImagesHelper $imagesHelper,
        JsonFactory $jsonFactory
    ) {
        $this->_catalogHelper = $catalogHelper;
        $this->_imagesHelper = $imagesHelper;
        $this->_jsonFactory = $jsonFactory;
    }

    /**
     * around execute
     *
     * @param mixed $interceptor
     * @param callable $proceed
     */
    public function aroundExecute(
        $interceptor,
        callable $proceed
    ) {
        if ($interceptor->getRequest()->getParam('module') == 'revslider') {

            $storeId = $interceptor->getRequest()->getParam('store');

            $filename = $interceptor->getRequest()->getParam('filename');
            $filename = $this->_imagesHelper->idDecode($filename);
            $asIs = $interceptor->getRequest()->getParam('as_is');

            $image = $this->_imagesHelper->getImageHtmlDeclaration($filename, $asIs);

            $data = ['image' => $image];
            $imagePath = $this->_imagesHelper->getCurrentPath() . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($imagePath) && $imageSize = getimagesize($imagePath)) {
                $data['width'] = isset($imageSize[0]) ? $imageSize[0] : '';
                $data['height'] = isset($imageSize[1]) ? $imageSize[1] : '';
            }

            $resultJson = $this->_jsonFactory->create();
            $result = $resultJson->setData($data);

        } else {
            $result = $proceed();
        }
        return $result;
    }

}