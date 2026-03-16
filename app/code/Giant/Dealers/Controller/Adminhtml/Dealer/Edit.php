<?php
namespace Giant\Dealers\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Giant\Dealers\Model\DealerFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Giant_Dealers::dealers';

    protected $resultPageFactory;
    protected $dealerFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        DealerFactory $dealerFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dealerFactory = $dealerFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('dealer_id');
        $dealer = $this->dealerFactory->create();

        if ($id) {
            $dealer->load($id);
            if (!$dealer->getId()) {
                $this->messageManager->addErrorMessage(__('Este distribuidor ya no existe.'));
                return $this->_redirect('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Giant_Dealers::dealers');
        $resultPage->getConfig()->getTitle()->prepend(
            $dealer->getId() ? $dealer->getData('name') : __('Nuevo Distribuidor')
        );
        return $resultPage;
    }
}
