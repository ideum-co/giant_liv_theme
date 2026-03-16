<?php
declare(strict_types=1);

namespace Giant\Checkout\Controller\Adminhtml\Distributor;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;

class NewAction extends Action
{
    const ADMIN_RESOURCE = 'Giant_Checkout::distributor';

    public function __construct(
        Context $context,
        private readonly ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->forwardFactory->create()->forward('edit');
    }
}
