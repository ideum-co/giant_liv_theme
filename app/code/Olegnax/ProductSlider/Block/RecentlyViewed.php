<?php
/*
 * @author      Olegnax
 * @package     Olegnax_ProductSlider
 * @copyright   Copyright (c) 2023 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Olegnax\ProductSlider\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Ui\Block\Wrapper;
use Magento\Ui\Model\UiComponentGenerator;
use Magento\Widget\Block\BlockInterface;

class RecentlyViewed extends Wrapper implements BlockInterface
{
    /**
     * @var Json
     */
    protected $json;

    public function __construct(
        Template\Context $context,
        UiComponentGenerator $uiComponentGenerator,
        Json $json = null,
        array $data = []
    ) {
        parent::__construct($context, $uiComponentGenerator, $data);
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * @param array $options
     * @param bool $json
     *
     * @return array|bool|false|string
     */
    public function getCarouselOptions($options = [], $json = true)
    {
        $autoplayTime = (int)$this->getAutoplayTime();
        if (!$autoplayTime || $autoplayTime < 500) {
            $autoplayTime = 500;
        }
        $options['itemClass'] = 'product-item';
        $options['margin'] = (int)$this->getMargin();
        $options['loop'] = (bool)$this->getLoop();
        $options['dots'] = (bool)$this->getDots();
        $options['nav'] = (bool)$this->getNav();
        $options['items'] = (int)$this->getColumnsDesktop();
        $options['autoplay'] = (bool)$this->getAutoplay();
        $options['autoplayTimeout'] = $autoplayTime;
        $options['autoplayHoverPause'] = (bool)$this->getPauseOnHover();
        $options['lazyLoad'] = true;
        $options['rewind'] = (bool)$this->getRewind();
        $options['responsive'] = [
            '0' => [
                'items' => max(1, (int)$this->getColumnsMobile()),
            ],
            '640' => [
                'items' => max(1, (int)$this->getColumnsTablet()),
            ],
            '1025' => [
                'items' => max(1, (int)$this->getColumnsDesktopSmall()),
            ],
            '1160' => [
                'items' => max(1, (int)$this->getColumnsDesktop()),
            ],
        ];

        if ($json) {
            return $this->json->serialize(['OXowlCarousel' => $options]);
        }

        return $options;
    }
}