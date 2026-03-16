<?php
declare(strict_types=1);

namespace Giant\Checkout\Controller\Adminhtml\Distributor;

use Giant\Checkout\Api\DistributorRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Giant_Checkout::distributor';

    public function __construct(
        Context $context,
        private readonly DistributorRepositoryInterface $distributorRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/index');
        $id = (int) $this->getRequest()->getParam('entity_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('ID de distribuidor no válido.'));
            return $redirect;
        }

        try {
            $this->distributorRepository->deleteById($id);
            $this->messageManager->addSuccessMessage(__('Distribuidor eliminado.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Error al eliminar el distribuidor.'));
        }

        return $redirect;
    }
}
