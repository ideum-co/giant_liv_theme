<?php
namespace Giant\Dealers\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Giant\Dealers\Model\DealerFactory;
use Giant\Dealers\Model\ResourceModel\Dealer as DealerResource;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Giant_Dealers::dealers';

    protected $dealerFactory;
    protected $dealerResource;

    public function __construct(
        Action\Context $context,
        DealerFactory $dealerFactory,
        DealerResource $dealerResource
    ) {
        $this->dealerFactory = $dealerFactory;
        $this->dealerResource = $dealerResource;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('dealer_id');
        if ($id) {
            try {
                $dealer = $this->dealerFactory->create();
                $this->dealerResource->load($dealer, $id);
                $this->dealerResource->delete($dealer);
                $this->messageManager->addSuccessMessage(__('Distribuidor eliminado correctamente.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error al eliminar el distribuidor.'));
            }
        }
        return $this->_redirect('*/*/');
    }
}
