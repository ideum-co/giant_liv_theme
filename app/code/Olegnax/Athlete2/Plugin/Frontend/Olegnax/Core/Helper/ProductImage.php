<?php

namespace Olegnax\Athlete2\Plugin\Frontend\Olegnax\Core\Helper;

use Closure;
use Exception;
use Magento\Catalog\Helper\Image;
use Olegnax\Athlete2\Helper\Image as Athlete2Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Framework\View\ConfigInterface;
use Olegnax\Athlete2\Helper\Video;
use Olegnax\Core\Helper\ProductImage as CoreProductImage;

class ProductImage
{    const PLACEHOLDERE = 'athlete2/placeholder.png';
    /**
     * @var Video
     */
    protected $_videoHelper;
    /**
     * @var Athlete2Image
     */
    protected  $_imageHelper;

    public function __construct(
        Athlete2Image $imageHelper,
        Video $videoHelper
    ) {
        $this->_videoHelper = $videoHelper;
        $this->_imageHelper = $imageHelper;
    }

    /**
     * @param CoreProductImage $subject
     * @param Closure $proceed
     * @param Product $product
     * @param $imageId
     * @param $imageId_hover
     *
     * @return bool
     */
    public function aroundHasHoverImage(
        CoreProductImage $subject,
        Closure $proceed,
        Product $product,
        $imageId,
        $imageId_hover
    ) {
        return $this->_videoHelper->videoOnHover($product) || $proceed($product, $imageId, $imageId_hover);
    }

    /**
     * @param CoreProductImage $subject
     * @param Closure $proceed
     * @param Product $product
     * @param $imageId
     * @param $size
     * @param $properties
     *
     * @return Image|Athlete2Image
     */
    public function aroundResizeImage(
        CoreProductImage $subject,
        Closure $proceed,
        Product $product,
        $imageId,
        $size,
        $properties = []
    ) {
        $_imageId = $subject->getImageParams($imageId);
        if ($this->_videoHelper->videoOnHover($product)
            && 'img_hover' === $_imageId['image_type']
        ) {
            $result = $this->getPlaceholderHelper();
        } else {
            $result = $proceed($product, $imageId, $size, $properties);
        }

        return $result;
    }

    /**
     * @return Athlete2Image
     * @throws Exception
     */
    protected function getPlaceholderHelper()
    {
        return $this->_imageHelper->init(
            static::PLACEHOLDERE,
            [
                'quality' => 1,
            ]
        );
    }
}
