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

namespace Olegnax\BannerSlider\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Group extends AbstractDb {

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct() {
		$this->_init('olegnax_bannerslider_group', 'group_id');
	}

	protected function _beforeSave( AbstractModel $object) {
		$storeId = $object->getStoreId();
		if (is_array($storeId)) {
			$object->setStoreId(implode(',', $storeId));
		}

		return parent::_beforeSave($object);
	}

	protected function _afterLoad( AbstractModel $object) {
		$storeId = $object->getStoreId();
		if (!is_array($storeId)) {
			$storeId = $storeId ? explode(',', $storeId) : 0;
			$object->setStoreId($storeId);
		}

		return parent::_afterLoad($object);
	}

}
