<?php

namespace Nwdthemes\Revslider\Plugin\Cms\Controller\Adminhtml\Wysiwyg\Images;

use Magento\Cms\Helper\Wysiwyg\Images as ImagesHelper;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Framework\Image\AdapterFactory as ImageAdapterFactory;

class ThumbnailPlugin
{
    /**
     * @var ImageAdapterFactory
     */
    protected $_imageAdapterFactory;

    /**
     * @var ImagesHelper
     */
    protected $_imagesHelper;

    /**
     * @var ResultRawFactory
     */
    protected $_resultRawFactory;

    /**
     * @param ImageAdapterFactory $imageAdapterFactory
     * @param ImagesHelper $imagesHelper
     * @param ResultRawFactory $resultRawFactory
     */
    public function __construct(
        ImageAdapterFactory $imageAdapterFactory,
        ImagesHelper $imagesHelper,
        ResultRawFactory $resultRawFactory
    ) {
        $this->_imageAdapterFactory = $imageAdapterFactory;
        $this->_imagesHelper = $imagesHelper;
        $this->_resultRawFactory = $resultRawFactory;
    }

    /**
     * after execute
     *
     * @param mixed $interceptor
     * @param callable $proceed
     */
    public function aroundExecute(
        $interceptor,
        callable $proceed
    ) {
        $file = $interceptor->getRequest()->getParam('file');
        $file = $this->_imagesHelper->getStorageRoot() . DIRECTORY_SEPARATOR . $this->_imagesHelper->idDecode($file);
        if (pathinfo($file, PATHINFO_EXTENSION) == 'webp' && file_exists($file)) {
            $resultRaw = $this->_resultRawFactory->create();
            $resultRaw->setHeader('Content-Type', 'image/webp');
            $resultRaw->setContents(file_get_contents($file));
            $result = $resultRaw;
        } else {
            $result = $proceed();
        }
        return $result;
    }

}