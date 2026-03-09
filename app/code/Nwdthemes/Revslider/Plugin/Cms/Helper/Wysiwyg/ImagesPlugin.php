<?php

namespace Nwdthemes\Revslider\Plugin\Cms\Helper\Wysiwyg;

use Magento\Framework\App\RequestInterface;
use Magento\Cms\Model\Wysiwyg\Config;
use Nwdthemes\Revslider\Helper\Images;

class ImagesPlugin
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->_request = $request;
    }

    /**
     * After getStorageRootSubpath
     *
     * @param mixed $interceptor
     * @param string $result
     * @return string
     */
    public function afterGetStorageRootSubpath(
        $interceptor,
        string $result
    ) {
        if ($this->_request->getParam('module') == 'revslider') {
            $result = Images::IMAGE_DIR;
        }
        return $result;
    }

    /**
     * After isUsingStaticUrlsAllowed
     *
     * @param mixed $interceptor
     * @param bool $result
     * @return bool
     */
    public function afterIsUsingStaticUrlsAllowed(
        $interceptor,
        bool $result
    ) {
        if ($this->_request->getParam('module') == 'revslider') {
            $result = true;
        }
        return $result;
    }

    /**
     * After getCurrentPath
     *
     * @param mixed $interceptor
     * @param string $result
     * @return string
     */
    public function afterGetCurrentPath(
        $interceptor,
        string $result
    ) {
        if ($this->_request->getParam('module') == 'revslider') {
            if ($this->_request->getParam($interceptor->getTreeNodeName()) == 'root') {
                $result = str_replace('/' . Config::IMAGE_DIRECTORY, '/' . Images::IMAGE_DIR, $result);
            }
        }
        return $result;
    }

    /**
     * After getStorageRoot
     *
     * @param mixed $interceptor
     * @param string $result
     * @return string
     */
    public function afterGetStorageRoot(
        $interceptor,
        string $result
    ) {
        if ($this->_request->getParam('module') == 'revslider') {
            if (strpos($result, '/' . Config::IMAGE_DIRECTORY) !== false && strpos($result, '/' . Images::IMAGE_DIR) === false) {
                $result = str_replace('/' . Config::IMAGE_DIRECTORY, '/' . Images::IMAGE_DIR, $result);
            }
        }
        return $result;
    }

}