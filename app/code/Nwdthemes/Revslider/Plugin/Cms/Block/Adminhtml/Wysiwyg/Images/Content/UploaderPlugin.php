<?php

namespace Nwdthemes\Revslider\Plugin\Cms\Block\Adminhtml\Wysiwyg\Images\Content;

use Magento\Framework\DataObject;

class UploaderPlugin
{
    /**
     * After retrieve config object
     *
     * @param mixed $interceptor
     * @param DataObject $result
     * @return DataObject
     */
    public function afterGetConfig(
        $interceptor,
        DataObject $result
    ) {
        if ($interceptor->getRequest()->getParam('module') == 'revslider' && $result->getUrl()) {
            $typeKey = '/type/';
            $params = ['module' => 'revslider'];
            if (strpos($result->getUrl(), $typeKey) !== false) {
                $params['type'] = explode('/', str_replace($typeKey, '', strstr($result->getUrl(), $typeKey)))[0];
            }
            $result->setUrl($interceptor->getUrl('cms/*/upload', $params));
        }
        return $result;
    }

}