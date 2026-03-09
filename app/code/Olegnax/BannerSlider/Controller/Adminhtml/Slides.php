<?php

/**
 * Olegnax BannerSlider
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
 * @package     Olegnax_BannerSlider
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\BannerSlider\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;

abstract class Slides extends Action {

	protected $_coreRegistry;
	protected $_slidesFactory;

	/**
	 * @param Context $context
	 * @param Registry $coreRegistry
	 */
	public function __construct(
	Context $context, Registry $coreRegistry
	) {
		$this->_coreRegistry = $coreRegistry;
		parent::__construct($context);
	}

	/**
	 * Init page
	 *
	 * @param Page $resultPage
	 * @return Page
	 */
	public function initPage($resultPage) {
		$resultPage->setActiveMenu('Olegnax_Core::Olegnax_Core')
				->addBreadcrumb(__('Banners'), __('Banners'))
				->addBreadcrumb(__('Banner Slides'), __('Banner Slides'));
		return $resultPage;
	}

}
