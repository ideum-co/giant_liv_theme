<?php
namespace Giant\BikeRegistration\Controller\Adminhtml\Registration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Giant_BikeRegistration::registration';

    protected $pageFactory;

    public function __construct(Context $context, PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Giant_BikeRegistration::registration');
        $page->getConfig()->getTitle()->prepend(__('Registros de Bicicletas'));
        return $page;
    }
}
