<?php

namespace Olegnax\BannerSlider\Model\Config\Source;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Option\ArrayInterface;
use Olegnax\Athlete2Slideshow\Helper\Helper;
use Olegnax\BannerSlider\Model\ResourceModel\Group\CollectionFactory;

class GroupName implements ArrayInterface {

	/**
	 *
	 * @var CollectionFactory
	 */
	protected $group;

	/**
	 *
	 * @var ObjectManager
	 */
	public $_objectManager;

	public function __construct( Helper $helper) {
		$this->_objectManager = ObjectManager::getInstance();
	}

	public function toOptionArray() {
		$options = [];
		if (!$this->group) {
			$this->group = $this->_objectManager->get('\Olegnax\BannerSlider\Model\ResourceModel\Group\CollectionFactory')->create();
		}
		if ($this->group && $this->group->getSize()) {
			$groups = $this->group->addFieldToSelect('*')->setOrder('group_name', 'asc');
			foreach ($groups as $group) {
				$options[] = [
					'value' => $group->getId(),
					'label' => $group->getGroupName()
				];
			}
		}

		return $options;
	}

}
