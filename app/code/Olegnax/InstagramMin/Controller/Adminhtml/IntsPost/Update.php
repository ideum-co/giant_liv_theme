<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\InstagramMin\Controller\Adminhtml\IntsPost;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\View\Result\PageFactory;
use Olegnax\InstagramMin\Helper\Helper;
use Olegnax\InstagramMin\Helper\Image;
use Olegnax\InstagramMin\Model\Client;
use Olegnax\InstagramMin\Model\IntsPost;
use Psr\Log\LoggerInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Update extends Action
{
    const UPLOAD_DIR = 'ox_instagram';
    const URL_PATH_ACCESSTOKEN = "oauth/access_token";

    protected $resultPageFactory;
    /**
     * @var Helper
     */
    protected $helper;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var IntsPost
     */
    protected $model;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var File
     */
    protected $file;
    /**
     * @var Image
     */
    protected $imageHelper;
    /**
     * @var EncryptorInterface $encryptor
     */
    protected $encryptor;
    /** 
     * @var Client $client 
    */
    protected $client;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Helper $helper
     * @param Image $imageHelper
     * @param LoggerInterface $logger
     * @param IntsPost $model
     * @param DirectoryList $directoryList
     * @param File $file
     * @param PageFactory $resultPageFactory
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Helper $helper,
        Image $imageHelper,
        LoggerInterface $logger,
        IntsPost $model,
        DirectoryList $directoryList,
        File $file,
        PageFactory $resultPageFactory,
        EncryptorInterface $encryptor,
        Client $client
    ) {
        $this->model = $model;
        $this->imageHelper = $imageHelper;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->resultPageFactory = $resultPageFactory;
        $this->encryptor = $encryptor;
        $this->client = $client;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return ResultInterface
     */
    public function execute()
    {
		$this->executeWithToken();

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    public function executeWithToken()
    {
        $images = [];
        try {
            $access_token = $this->encryptor->decrypt($this->helper->getModuleConfig(static::URL_PATH_ACCESSTOKEN));
            $user_id = $this->helper->getModuleConfig('oauth/user_id');
            $images = $this->client->setToken($access_token)->setUserId($user_id)->getUserMedia();
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        if (!empty($images)) {
            $existposts = [];
            $itemCollection = $this->model->getCollection()->load();
            foreach ($itemCollection as $item) {
                $existposts[(int)$item->getIntsId()] = $item->getID();
            }
            $addedPosts = 0;
            $updatePosts = 0;
            foreach ($images as $item) {
                $id = $item['id'];
                $exist = array_key_exists($id, $existposts);
                try {
                    if ($exist) {
                        $dbitem = $this->model->load($existposts[$id]);
                        $dbitem->addData([
                            'edge_media_to_caption' => isset($item['caption']) ? $item['caption'] : '',
                            'edge_media_to_comment' => isset($item['comments_count']) ? (int)$item['comments_count'] : 0,
                            'taken_at_timestamp' => $item['timestamp'],
                            'edge_liked_by' => isset($item['like_count']) ? (int)$item['like_count'] : 0,
                            'edge_media_preview_like' => isset($item['like_count']) ? (int)$item['like_count'] : 0,
                            'location' => '',
                            'video_view_count' => 0,
                        ])->save();
                        $updatePosts++;
                    } else {
                        $this->model->setData($this->prepareTokenItem($item))->save();
                        $addedPosts++;
                    }
                } catch (Exception $e) {
                    $this->messageManager->addErrorMessage(__('Something went wrong!'));
                    $this->logger->error($e->getMessage(), $item);
                }
            }
            $smessage = [];

            if (0 < $addedPosts) {
                $smessage[] = __("Added \"%1\" new posts!", $addedPosts);
            }
            if (0 < $updatePosts) {
                $smessage[] = __("Updated \"%1\" posts!", $updatePosts);
            }

            if (!empty($smessage)) {
                $this->messageManager->addSuccessMessage(implode(" ", $smessage));
            }
        } else {
            $this->messageManager->addWarningMessage(__('No posts were found! Perhaps the profile is private?'));
        }
    }

    private function prepareTokenItem($item)
    {
        $return = [
            'ints_id' => $item['id'],
            'owner' => $item['username'],
            'edge_media_to_caption' => isset($item['caption']) ? $item['caption'] : '',
            'typename' => $item['media_type'],
            'shortcode' => $item['shortcode'],
            'display_url' => $item['media_url'],
            'taken_at_timestamp' => $item['timestamp'],
            'dimensions_width' => 0,
            'dimensions_height' => 0,
        ];

        return $this->prepareItem($return);
    }

    protected function prepareItem($item)
    {
        if (is_array($item['display_url'])) {
            foreach ($item['display_url'] as &$url) {
                $url = $this->downloadImages($url);
            }
        } else {
            $item['display_url'] = $this->downloadImages($item['display_url']);
        }

        if (empty($item['dimensions_width']) || empty($item['dimensions_height'])) {
            try {
                $image = is_array($item['display_url']) ? $item['display_url'][0] : $item['display_url'];
                $this->imageHelper->init($image);
                $item['dimensions_width'] = $this->imageHelper->getOriginalWidth();
                $item['dimensions_height'] = $this->imageHelper->getOriginalHeight();

            } catch (Exception $e) {
                $this->logger->error("Instagram:" . $e);
                $item['dimensions_width'] = 0;
                $item['dimensions_height'] = 0;
            }
        }

        return $item;
    }

    protected function downloadImages($imageUrl, $prefix = '')
    {
        if (empty($imageUrl)) {
            return $imageUrl;
        }
        $fileName = $prefix . baseName(parse_url($imageUrl, PHP_URL_PATH));
        $tempFileName = $this->getMediaDirTmpDir() . $fileName;
        $newFileName = $this->getMediaDirDestDir() . $fileName;

        if ($this->file->fileExists($newFileName, true)) {
            return $this->prepareUploadFile($newFileName);
        }

        try {
            $this->file->checkAndCreateFolder(dirname($tempFileName));
            $result = $this->file->read($imageUrl, $tempFileName);
            if ($result) {
                $this->file->checkAndCreateFolder(dirname($newFileName));
                $result = $this->file->mv($tempFileName, $newFileName);
                return $this->prepareUploadFile($result ? $newFileName : $tempFileName);
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $imageUrl;
    }

    protected function getMediaDirTmpDir()
    {
        return $this->getMediaDir() . '/tmp/';
    }

    protected function getMediaDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA);
    }

    protected function getMediaDirDestDir()
    {
        return $this->getMediaDir() . '/' . static::UPLOAD_DIR . '/';
    }

    protected function prepareUploadFile($path)
    {
        return str_replace($this->getMediaDir() . '/', '', $path);
    }

}

