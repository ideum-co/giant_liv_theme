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

namespace Olegnax\BannerSlider\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Olegnax\BannerSlider\Model\Group;

class Edit extends \Olegnax\BannerSlider\Controller\Adminhtml\Group {

    const ADMIN_RESOURCE = 'Olegnax_BannerSlider::Group_Edit';

	protected $resultPageFactory;

	public function __construct(
	Context $context, Registry $coreRegistry, PageFactory $resultPageFactory
	) {
		$this->resultPageFactory = $resultPageFactory;
		parent::__construct($context, $coreRegistry);
	}

	/**
	 * Edit action
	 *
	 * @return ResultInterface
	 */
	public function execute() {
		$id = $this->getRequest()->getParam('id');
		$model = $this->_objectManager->create( Group::class);

		if ($id) {
			$model->load($id);
			if (!$model->getId()) {
				$this->messageManager->addErrorMessage(__('This Group no longer exists.'));
				$resultRedirect = $this->resultRedirectFactory->create();
				return $resultRedirect->setPath('*/*/');
			}
		}

		$this->_coreRegistry->register('olegnax_bannerslider_group', $model);

		$resultPage = $this->resultPageFactory->create();
		$this->initPage($resultPage)->addBreadcrumb(
				$id ? __('Edit Group') : __('New Group'),
				$id ? __('Edit Group') : __('New Group')
		);
		$resultPage->getConfig()->getTitle()->prepend(__('Group'));
		$resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Group %1',
								$model->getId()) : __('New Group') );
		return $resultPage;
	}

}
