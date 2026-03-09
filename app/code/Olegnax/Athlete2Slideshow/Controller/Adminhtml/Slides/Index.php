<?php

namespace Olegnax\Athlete2Slideshow\Controller\Adminhtml\Slides;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action {

    const ADMIN_RESOURCE = 'Olegnax_Athlete2Slideshow::Slides_Index';

    protected $_pageFactory;

    public function __construct(
    Context $context, PageFactory $pageFactory) {
	$this->_pageFactory = $pageFactory;
	return parent::__construct($context);
    }

    public function execute() {
	$resultPage = $this->_pageFactory->create();
	$resultPage->getConfig()->getTitle()->prepend((__('Athlete Slide Manager')));

	return $resultPage;
    }

}
