<?php
declare(strict_types=1);

namespace Giant\Checkout\Controller\Adminhtml\Distributor;

use Giant\Checkout\Model\ResourceModel\Distributor\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Giant_Checkout::distributor';

    public function __construct(
        Context $context,
        private readonly Filter            $filter,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count      = 0;

        foreach ($collection as $distributor) {
            $distributor->delete();
            $count++;
        }

        $this->messageManager->addSuccessMessage(
            __('Se eliminaron %1 distribuidor(es).', $count)
        );

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
