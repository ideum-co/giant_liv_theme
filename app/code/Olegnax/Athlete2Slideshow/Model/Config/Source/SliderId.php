<?php

/**
 * Olegnax Athlete Slideshow
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Olegnax.com license that is
 * available through the world-wide-web at this URL:
 * https://www.olegnax.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Olegnax
 * @package     Olegnax_AthleteSlideshow
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */


namespace Olegnax\Athlete2Slideshow\Model\Config\Source;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Option\ArrayInterface;
use Nwdthemes\Revslider\Model\ResourceModel\Slider\CollectionFactory;
use Olegnax\Athlete2Slideshow\Helper\Helper;

class SliderId implements ArrayInterface {

	/**
	 *
	 * @var CollectionFactory
	 */
	protected $revslider;
	/**
	 *
	 * @var ObjectManager
	 */
	public $_objectManager;

	/**
	 *
	 * @var Helper
	 */
	public $_helper;

	public function __construct( Helper $helper) {
		$this->_objectManager = ObjectManager::getInstance();
		$this->_helper = $helper;
	}

	public function toOptionArray() {
		$options = [];
		if ($this->_helper->isRevoulutionModuleActive()) {
			if (!$this->revslider) {
				$this->revslider = $this->_objectManager->get('\Nwdthemes\Revslider\Model\ResourceModel\Slider\CollectionFactory')->create();
			}

			if ($this->revslider && $this->revslider->getSize()) {
				$sliders = $this->revslider->addFieldToSelect('*')->setOrder('title', 'asc');
				foreach ($sliders as $slider) {
					$options[] = [
						'value' => $slider->getAlias(),
						'label' => $slider->getTitle()
					];
				}
			}
		}

		return $options;
	}

}
