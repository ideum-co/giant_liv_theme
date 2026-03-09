<?php

namespace Olegnax\BannerSlider\Block\Adminhtml\Group\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

class General extends Generic implements TabInterface {

	/**
	 * @var Store
	 */
	protected $_systemStore;

	public function __construct(
		Store $systemStore,
		Context $context,
		Registry $registry,
		FormFactory $formFactory,
		array $data = []
	) {
		$this->_systemStore = $systemStore;
		parent::__construct($context, $registry, $formFactory, $data);
	}

	protected function _prepareForm() {
		$model = $this->_coreRegistry->registry('olegnax_bannerslider_group');
		$form = $this->_formFactory->create();
		$fieldset = $form->addFieldset(
				'base_fieldset', ['legend' => __('General')]
		);

		if ($model->getId()) {
			$fieldset->addField(
					'group_id', 'hidden', ['name' => 'group_id']
			);
		}

		$fieldset->addField('group_name', 'text', array(
			'label' => __('Group name'),
			'name' => 'group_name',
			'required' => true
		));
		$fieldset->addField('identifier', 'text', array(
			'label' => __('Identifier'),
			'name' => 'identifier',
			'required' => true
		));
		$fieldset->addField('slide_width', 'text', array(
			'label' => __('Slide width'),
			'name' => 'slide_width',
			'required' => true
		));
		$fieldset->addField('slide_height', 'text', array(
			'label' => __('Slide height'),
			'name' => 'slide_height',
			'required' => true
		));

		$data = $model->getData();
		$form->setValues($data);
		$this->setForm($form);

		return parent::_prepareForm();
	}

	public function getTabLabel() {
		return __('General');
	}

	public function getTabTitle() {
		return __('General');
	}

	public function canShowTab() {
		return true;
	}

	public function isHidden() {
		return false;
	}

}
