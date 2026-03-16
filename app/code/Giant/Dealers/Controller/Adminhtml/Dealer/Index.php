<?php
namespace Giant\Dealers\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Giant_Dealers::dealers';

    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Giant_Dealers::dealers');
        $resultPage->getConfig()->getTitle()->prepend(__('Distribuidores'));
        return $resultPage;
    }
}
