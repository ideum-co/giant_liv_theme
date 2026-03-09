<?php

namespace Nwdthemes\Revslider\Plugin\Cms\Block\Adminhtml\Wysiwyg\Images;

use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;

class ContentPlugin
{
    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param DecoderInterface $jsonDecoder
     * @param EncoderInterface $jsonEncoder
     */
    public function __construct(
        DecoderInterface $jsonDecoder,
        EncoderInterface $jsonEncoder
    ) {
        $this->_jsonDecoder = $jsonDecoder;
        $this->_jsonEncoder = $jsonEncoder;
    }

    /**
     * after getFilebrowserSetupObject
     *
     * @param mixed $interceptor
     * @param string $resultJson
     * @return string
     */
    public function afterGetFilebrowserSetupObject(
        $interceptor,
        $resultJson
    ) {
        if ($interceptor->getRequest()->getParam('module') == 'revslider') {
            $result = $this->_jsonDecoder->decode($resultJson);
            $result['contentsUrl'] = $interceptor->getUrl('cms/*/contents', ['type' => $interceptor->getRequest()->getParam('type'), 'module' => 'revslider']);
            $result['onInsertUrl'] = $interceptor->getUrl('cms/*/onInsert', ['module' => 'revslider']);
            $result['newFolderUrl'] = $interceptor->getUrl('cms/*/newFolder', ['module' => 'revslider']);
            $result['deleteFolderUrl'] = $interceptor->getUrl('cms/*/deleteFolder', ['module' => 'revslider']);
            $result['deleteFilesUrl'] = $interceptor->getUrl('cms/*/deleteFiles', ['module' => 'revslider']);
            $resultJson = $this->_jsonEncoder->encode($result);
        }
        return $resultJson;
    }

}