<?php
declare(strict_types=1);

namespace Giant\Checkout\Block\Adminhtml\Distributor;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Giant\Checkout\Api\DistributorRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Container
{
    public function __construct(
        Context $context,
        private readonly DistributorRepositoryInterface $distributorRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        $this->_objectId   = 'entity_id';
        $this->_blockGroup = 'Giant_Checkout';
        $this->_controller = 'adminhtml_distributor';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Guardar Distribuidor'));
        $this->buttonList->add(
            'save_and_continue',
            [
                'label'          => __('Guardar y Continuar Editando'),
                'class'          => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit']],
                ],
            ],
            -100
        );
    }

    public function getHeaderText(): \Magento\Framework\Phrase
    {
        $id = (int) $this->getRequest()->getParam('entity_id');

        if ($id) {
            try {
                $distributor = $this->distributorRepository->getById($id);
                return __("Editar Distribuidor '%1'", $this->escapeHtml($distributor->getName()));
            } catch (NoSuchEntityException) {}
        }

        return __('Nuevo Distribuidor');
    }
}
