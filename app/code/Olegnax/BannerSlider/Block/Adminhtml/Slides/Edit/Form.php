<?php

namespace Olegnax\BannerSlider\Block\Adminhtml\Slides\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

class Form extends Generic {

	protected $_systemStore;

	public function __construct(
	Context $context, Registry $registry, FormFactory $formFactory, Store $systemStore, array $data = []
	) {
		$this->_systemStore = $systemStore;
		parent::__construct($context, $registry, $formFactory, $data);
	}

	protected function _construct() {
		parent::_construct();
		$this->setId('slides_form');
		$this->setTitle(__('Slide'));
	}

	protected function _prepareForm() {
		$form = $this->_formFactory->create(
				[
					'data' => [
						'id' => 'edit_form',
						'action' => $this->getData('action'),
						'enctype' => 'multipart/form-data',
						'method' => 'post',
					]
				]
		);
		$form->setUseContainer(true);
		$this->setForm($form);

		return parent::_prepareForm();
	}

}
