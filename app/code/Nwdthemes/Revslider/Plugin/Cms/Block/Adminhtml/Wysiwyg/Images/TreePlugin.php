<?php

namespace Nwdthemes\Revslider\Plugin\Cms\Block\Adminhtml\Wysiwyg\Images;

class TreePlugin
{
    /**
     * after getTreeLoaderUrl
     *
     * @param mixed $interceptor
     * @param string $result
     * @return string
     */
    public function afterGetTreeLoaderUrl(
        $interceptor,
        $result
    ) {
        if ($interceptor->getRequest()->getParam('module') == 'revslider') {
            $params = ['module' => 'revslider'];
            $currentTreePath = $interceptor->getRequest()->getParam('current_tree_path');
            if ($currentTreePath !== null && strlen($currentTreePath)) {
                $params['current_tree_path'] = $currentTreePath;
            }
            $result = $interceptor->getUrl('cms/*/treeJson', $params);
        }
        return $result;
    }

    /**
     * after getRootNodeName
     *
     * @param mixed $interceptor
     * @param string $result
     * @return string
     */
    public function afterGetRootNodeName(
        $interceptor,
        $result
    ) {
        if ($interceptor->getRequest()->getParam('module') == 'revslider') {
            $result = __('Slider Revolution Media Gallery');
        }
        return $result;
    }

}