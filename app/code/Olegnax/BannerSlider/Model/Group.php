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

namespace Olegnax\BannerSlider\Model;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class Group extends AbstractModel {

	protected $directoryList;
	protected $io;
	protected $_storeManager;
	protected $_eventPrefix = 'olegnax_bannerslider_group';

	public function __construct(
	Context $context, Registry $registry,
 StoreManagerInterface $storeManager, DirectoryList $directoryList,
 File $io
	) {
		$this->_storeManager = $storeManager;
		$this->directoryList = $directoryList;
		$this->io			 = $io;
		parent::__construct( $context, $registry );
	}

	/**
	 * @return void
	 */
	protected function _construct() {
		$this->_init( \Olegnax\BannerSlider\Model\ResourceModel\Group::class );
	}

	public function getIdentities() {
		return [ $this->_eventPrefix . '_' . $this->getId() ];
	}

	public function getIdentifier() {
		return $this->getData( 'identifier' );
	}

}
