<?php
declare(strict_types=1);

namespace Giant\Checkout\Controller\Adminhtml\Distributor;

use Giant\Checkout\Api\DistributorRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Giant_Checkout::distributor';

    public function __construct(
        Context $context,
        private readonly PageFactory                   $resultPageFactory,
        private readonly DistributorRepositoryInterface $distributorRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('entity_id');
        $isNew = ($id === 0);

        if (!$isNew) {
            try {
                $this->distributorRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('Distribuidor no encontrado.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(
            $isNew ? __('Nuevo Distribuidor') : __('Editar Distribuidor')
        );

        return $resultPage;
    }
}
