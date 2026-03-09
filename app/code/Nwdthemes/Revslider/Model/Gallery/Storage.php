<?php

namespace Nwdthemes\Revslider\Model\Gallery;

use Nwdthemes\Revslider\Helper\Data;
use Nwdthemes\Revslider\Helper\Images;
use Magento\Cms\Model\Wysiwyg\Images\Storage as WysiwygImagesStorage;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Storage extends WysiwygImagesStorage {

    const MEDIA_GALLERY_IMAGE_FOLDERS_CONFIG_PATH
        = 'system/media_storage_configuration/allowed_resources/media_gallery_image_folders';

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    private $logger;

    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     */
    private $file;

    /**
     * @var \Magento\Framework\Filesystem\Io\File|null
     */
    private $ioFile;

    /**
     * @var \Magento\Framework\File\Mime|null
     */
    private $mime;

    /**
     * @var ScopeConfigInterface
     */
    private $coreConfig;

    /**
     * @var string
     */
    private $allowedPathPattern;

    /**
     * @var array
     */
    private $allowedDirs;

    /**
     * @var boolean
     */
    protected $_isRevsliderModule;

    /**
     * If we are on prehistoric 2.1.x version
     *
     * @var boolean
     */
    protected $_isLegacy21x = false;

    /**
     * If we are on an ancient 2.2.x version
     *
     * @var boolean
     */
    protected $_isLegacy22x = false;

    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Cms\Helper\Wysiwyg\Images $cmsWysiwygImages,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Cms\Model\Wysiwyg\Images\Storage\CollectionFactory $storageCollectionFactory,
        \Magento\MediaStorage\Model\File\Storage\FileFactory $storageFileFactory,
        \Magento\MediaStorage\Model\File\Storage\DatabaseFactory $storageDatabaseFactory,
        \Magento\MediaStorage\Model\File\Storage\Directory\DatabaseFactory $directoryDatabaseFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        RequestInterface $request,
        array $resizeParameters = [],
        array $extensions = [],
        array $dirs = [],
        array $data = []
    ) {
        $this->_isRevsliderModule = $request->getParam('module') == 'revslider';

        if ($this->_isRevsliderModule) {
            $extendExtensions = [
                'allowed' => [
                    'webp'   => 'image/webp'
                ],
                'image_allowed' => [
                    'webp'   => 'image/webp'
                ],
                'video_allowed' => [
                    'mp4'   => 1,
                    'mp3'   => 1,
                    'webm'  => 1,
                    'ogv'   => 1,
                    'avi'   => 1
                ]
            ];
            foreach ($extendExtensions as $key => $value) {
                $extendedExtensions[$key] = array_merge(isset($extensions[$key]) ? $extensions[$key] : [], $value);
            }
            $extensions = $extendedExtensions;
        }

        parent::__construct(
            $session,
            $backendUrl,
            $cmsWysiwygImages,
            $coreFileStorageDb,
            $filesystem,
            $imageFactory,
            $assetRepo,
            $storageCollectionFactory,
            $storageFileFactory,
            $storageDatabaseFactory,
            $directoryDatabaseFactory,
            $uploaderFactory,
            $resizeParameters,
            $extensions,
            $dirs,
            $data
        );

        $this->coreConfig = ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->logger = ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $this->file = ObjectManager::getInstance()->get(\Magento\Framework\Filesystem\Driver\File::class);
        $this->ioFile = ObjectManager::getInstance()->get(\Magento\Framework\Filesystem\Io\File::class);
        $this->mime = ObjectManager::getInstance()->get(\Magento\Framework\File\Mime::class);

        // Check if we are on legacy version
        $reflectionClass = new \ReflectionClass(WysiwygImagesStorage::class);
        try {
            $reflectionClass->getMethod('getAllowedMimeTypes');
        } catch (\Exception $e) {
            Data::log('Legacy 2.1.x code detected', $e->getMessage());
            $this->_isLegacy21x = true;
        }
        try {
            $reflectionClass->getMethod('isDirectoryAllowed');
        } catch (\Exception $e) {
            Data::log('Legacy 2.2.x code detected', $e->getMessage());
            $this->_isLegacy22x = true;
        }
    }

    /**
     * Return one-level child directories for specified path
     *
     * @param string $path Parent directory path
     * @return \Magento\Framework\Data\Collection\Filesystem
     * @throws \Exception
     */
    public function getDirsCollection($path)
    {
        $this->createSubDirectories($path);

        $collection = $this->getCollection($path)
            ->setCollectDirs(true)
            ->setCollectFiles(false)
            ->setCollectRecursively(false)
            ->setOrder('basename', \Magento\Framework\Data\Collection\Filesystem::SORT_ORDER_ASC);

        if (!$this->isDirectoryAllowed($path)) {
            $collection->setDirsFilter($this->getAllowedDirMask($path));
        }

        return $collection;
    }

    /**
     * Return files
     *
     * @param   string $path Parent directory path
     * @param   string $type Type of storage, e.g. image, media etc.
     * @return  \Magento\Framework\Data\Collection\Filesystem
     *
     * @throws  \Magento\Framework\Exception\FileSystemException
     * @throws  \Magento\Framework\Exception\LocalizedException
     */
    public function getFilesCollection($path, $type = null)
    {
        $collectFiles = $this->isDirectoryAllowed($path);

        if ($this->_coreFileStorageDb->checkDbUsage()) {
            $files = $this->_storageDatabaseFactory->create()->getDirectoryFiles($path);

            /** @var \Magento\MediaStorage\Model\File\Storage\File $fileStorageModel */
            $fileStorageModel = $this->_storageFileFactory->create();
            foreach ($files as $file) {
                $fileStorageModel->saveFile($file);
            }
        }

        $collection = $this->getCollection(
            $path
        )->setCollectDirs(
            false
        )->setCollectFiles(
            $collectFiles
        )->setCollectRecursively(
            false
        )->setOrder(
            'mtime',
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        // Add files extension filter
        if ($allowed = $this->getAllowedExtensions($type)) {
            $collection->setFilesFilter('/\.(' . implode('|', $allowed) . ')$/i');
        }

        foreach ($collection as $item) {
            $item->setId($this->_cmsWysiwygImages->idEncode($item->getBasename()));
            $item->setName($item->getBasename());
            $item->setShortName($this->_cmsWysiwygImages->getShortFilename($item->getBasename()));
            $item->setUrl($this->_cmsWysiwygImages->getCurrentUrl() . $item->getBasename());
            $driver = $this->_directory->getDriver();
            $itemStats = $driver->stat($item->getFilename());
            $item->setSize($itemStats['size']);
            $mimeType = $itemStats['mimetype'] ?? $this->mime->getMimeType($item->getFilename());
            $item->setMimeType($mimeType);

            if ($this->isImage($item->getBasename())) {
                $thumbUrl = $this->getThumbnailUrl($item->getFilename(), true);
                // generate thumbnail "on the fly" if it does not exists
                if (!$thumbUrl) {
                    $thumbOptions = [
                        'file' => $item->getId(),
                    ];
                    if ($this->_isRevsliderModule) {
                        $thumbOptions['module'] = 'revslider';
                    }
                    $thumbUrl = $this->_backendUrl->getUrl('cms/*/thumbnail', $thumbOptions);
                }

                try {
                    $size = getimagesizefromstring(
                        $driver->fileGetContents($item->getFilename())
                    );

                    if (is_array($size)) {
                        $item->setWidth($size[0]);
                        $item->setHeight($size[1]);
                    }
                } catch (\Error $e) {
                    $this->logger->notice(sprintf("GetImageSize caused error: %s", $e->getMessage()));
                }
            } else {
                $thumbUrl = $this->_assetRepo->getUrl(self::THUMB_PLACEHOLDER_PATH_SUFFIX);
            }

            $item->setThumbUrl($thumbUrl);
        }

        return $collection;
    }

    /**
     * Create new directory in storage
     *
     * @param string $name New directory name
     * @param string $path Parent directory path
     * @return array New directory info
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createDirectory($name, $path)
    {
        if (!preg_match(self::DIRECTORY_NAME_REGEXP, $name)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please rename the folder using only Latin letters, numbers, underscores and dashes.')
            );
        }

        if (!($this->isDirectoryAllowed(rtrim($path, '/') . '/' . $name))) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We cannot create the folder under the selected directory.')
            );
        }

        $relativePath = (string) $this->_directory->getRelativePath($path);
        if (!$this->_directory->isDirectory($relativePath) || !$this->_directory->isWritable($relativePath)) {
            $path = $this->_cmsWysiwygImages->getStorageRoot();
        }

        $newPath = rtrim($path, '/') . '/' . $name;
        $relativeNewPath = $this->_directory->getRelativePath($newPath);
        if ($this->_directory->isDirectory($relativeNewPath)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found a directory with the same name. Please try another folder name.')
            );
        }

        $this->_directory->create($relativeNewPath);
        try {
            if ($this->_coreFileStorageDb->checkDbUsage()) {
                $relativePath = $this->_coreFileStorageDb->getMediaRelativePath($newPath);
                $this->_directoryDatabaseFactory->create()->createRecursive($relativePath);
            }

            $result = [
                'name' => $name,
                'short_name' => $this->_cmsWysiwygImages->getShortFilename($name),
                'path' => $newPath,
                'id' => $this->_cmsWysiwygImages->convertPathToId($newPath),
            ];
            return $result;
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot create a new directory.'));
        }
    }

    /**
     * Recursively delete directory from storage
     *
     * @param string $path Absolute path to target directory
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteDirectory($path)
    {
        if (!$this->isDirectoryAllowed(dirname($path))) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We cannot delete the selected directory.')
            );
        }

        if ($this->_coreFileStorageDb->checkDbUsage()) {
            $this->_directoryDatabaseFactory->create()->deleteDirectory($path);
        }

        try {
            $this->_deleteByPath($path);
            $path = $this->getThumbnailRoot() . $this->_getRelativePathToRoot($path);
            $this->_deleteByPath($path);
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We cannot delete directory %1.', $this->_getRelativePathToRoot($path))
            );
        }
    }

    /**
     * Delete file (and its thumbnail if exists) from storage
     *
     * @param string $target File path to be deleted
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function deleteFile($target)
    {
        if (!$this->isDirectoryAllowed(dirname($target))) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t delete the file right now.')
            );
        }
        $relativePath = $this->_directory->getRelativePath($target);
        if ($this->_directory->isFile($relativePath)) {
            $this->_directory->delete($relativePath);
        }
        $this->_coreFileStorageDb->deleteFile($target);

        $thumb = $this->getThumbnailPath($target, true);
        $relativePathThumb = $this->_directory->getRelativePath($thumb);
        if ($thumb) {
            if ($this->_directory->isFile($relativePathThumb)) {
                $this->_directory->delete($relativePathThumb);
            }
            $this->_coreFileStorageDb->deleteFile($thumb);
        }
        return $this;
    }

    /**
     * Upload and resize new file
     *
     * @param string $targetPath Target directory
     * @param string $type Type of storage, e.g. image, media etc.
     * @return array File info Array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadFile($targetPath, $type = null)
    {
        if (!($this->isDirectoryAllowed($targetPath))) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t upload the file to the current folder right now. Please try another folder.')
            );
        }
        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => 'image']);
        $allowed = $this->getAllowedExtensions($type);
        if ($allowed) {
            $uploader->setAllowedExtensions($allowed);
        }
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        if (! $this->_isLegacy21x && !$uploader->checkMimeType($this->getAllowedMimeTypes($type))) {
            throw new \Magento\Framework\Exception\LocalizedException(__('File validation failed.'));
        }
        $result = $uploader->save($targetPath);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t upload the file right now.'));
        }

        // create thumbnail
        if ($type != 'video') {
            $this->resizeFile(rtrim($targetPath, '/') . '/' . ltrim($uploader->getUploadedFileName(), '/'), true);
        }

        return $result;
    }

    /**
     * Prepare mime types config settings.
     *
     * @param string|null $type Type of storage, e.g. image, media etc.
     * @return array Array of allowed file extensions
     */
    private function getAllowedMimeTypes($type = null): array
    {
        $allowed = $this->getExtensionsList($type);
        if ($type == 'video') {
            $allowed = array_keys(array_filter($allowed));
            foreach ($allowed as $key => $item) {
                $allowed[$key] = $type . '/' . $item;
            }
        } else {
            $allowed = array_values(array_filter($allowed));
        }
        return $allowed;
    }


    /**
     * Get list of allowed file extensions with mime type in values.
     *
     * @param string|null $type
     * @return array
     */
    private function getExtensionsList($type = null): array
    {
        if (is_string($type) && array_key_exists("{$type}_allowed", $this->_extensions)) {
            $allowed = $this->_extensions["{$type}_allowed"];
        } else {
            $allowed = $this->_extensions['allowed'];
        }

        return $allowed;
    }

    /**
     * Check if directory is allowed
     *
     * @param string $directoryPath Absolute path to a directory
     * @return bool
     */
    private function isDirectoryAllowed($directoryPath): bool
    {
        if ($this->_isRevsliderModule) {
            // revslider module
            $storageRoot = $this->_cmsWysiwygImages->getStorageRoot();
            $storageRootLength = strlen($storageRoot) - strlen(Images::IMAGE_DIR);
            $mediaSubPathname = substr($directoryPath, $storageRootLength);
            if (!$mediaSubPathname) {
                return false;
            }
            $mediaSubPathname = ltrim($mediaSubPathname, '/');
            return strpos($mediaSubPathname, Images::IMAGE_DIR) === 0;
        } elseif ($this->_isLegacy22x) {
            // no such method in older version so just allow it
            return true;
        } else {
            // original code
            $storageRoot = $this->_cmsWysiwygImages->getStorageRoot();
            $storageRootLength = strlen($storageRoot);
            $mediaSubPathname = $directoryPath === null ? '' : substr($directoryPath, $storageRootLength);
            if (!$mediaSubPathname) {
                return false;
            }
            $mediaSubPathname = ltrim($mediaSubPathname, '/');
            return preg_match($this->getAllowedPathPattern(), $mediaSubPathname) == 1;
        }
    }

    /**
     * Get allowed path pattern
     *
     * @return string
     */
    private function getAllowedPathPattern()
    {
        if (null === $this->allowedPathPattern) {
            $mediaGalleryImageFolders = $this->coreConfig->getValue(
                self::MEDIA_GALLERY_IMAGE_FOLDERS_CONFIG_PATH,
                'default'
            );
            $regExp = '/^(';
            $or = '';
            foreach ($mediaGalleryImageFolders as $folder) {
                $folderPattern = str_replace('/', '[\/]+', $folder ?? '');
                $regExp .= $or . $folderPattern . '\b(?!-)(?:\/?[a-zA-Z0-9\-\_]+)*\/?$';
                $or = '|';
            }
            $regExp .= ')/';
            $this->allowedPathPattern = $regExp;
        }
        return $this->allowedPathPattern;
    }

}
