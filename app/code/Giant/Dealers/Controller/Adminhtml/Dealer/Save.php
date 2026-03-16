<?php
namespace Giant\Dealers\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Giant\Dealers\Model\DealerFactory;
use Giant\Dealers\Model\ResourceModel\Dealer as DealerResource;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Giant_Dealers::dealers';

    protected $dealerFactory;
    protected $dealerResource;
    protected $logger;

    public function __construct(
        Action\Context $context,
        DealerFactory $dealerFactory,
        DealerResource $dealerResource,
        LoggerInterface $logger
    ) {
        $this->dealerFactory = $dealerFactory;
        $this->dealerResource = $dealerResource;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $this->_redirect('*/*/');
        }

        $id = $this->getRequest()->getParam('dealer_id');
        $dealer = $this->dealerFactory->create();

        if ($id) {
            $this->dealerResource->load($dealer, $id);
            if (!$dealer->getId()) {
                $this->messageManager->addErrorMessage(__('Este distribuidor ya no existe.'));
                return $this->_redirect('*/*/');
            }
        }

        if (isset($data['logo']) && is_array($data['logo'])) {
            if (!empty($data['logo'][0]['name'])) {
                $data['logo'] = 'dealers/' . $data['logo'][0]['name'];
            } else {
                $data['logo'] = null;
            }
        } elseif (isset($data['logo']) && is_string($data['logo'])) {
        } else {
            $data['logo'] = null;
        }

        unset($data['form_key']);

        try {
            $dealer->setData($data);
            if ($id) {
                $dealer->setId($id);
            }
            $this->dealerResource->save($dealer);
            $this->messageManager->addSuccessMessage(__('Distribuidor guardado correctamente.'));

            if ($this->getRequest()->getParam('back')) {
                return $this->_redirect('*/*/edit', ['dealer_id' => $dealer->getId()]);
            }
            return $this->_redirect('*/*/');
        } catch (\Exception $e) {
            $this->logger->error('Dealer save error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Error al guardar el distribuidor.'));
            return $this->_redirect('*/*/edit', ['dealer_id' => $id]);
        }
    }
}
