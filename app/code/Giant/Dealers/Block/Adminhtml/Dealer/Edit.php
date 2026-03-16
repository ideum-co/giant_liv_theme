<?php
namespace Giant\Dealers\Block\Adminhtml\Dealer;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Giant\Dealers\Model\DealerFactory;

class Edit extends Container
{
    protected $dealerFactory;

    public function __construct(
        Context $context,
        DealerFactory $dealerFactory,
        array $data = []
    ) {
        $this->dealerFactory = $dealerFactory;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'dealer_id';
        $this->_blockGroup = 'Giant_Dealers';
        $this->_controller = 'adminhtml_dealer';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Guardar Distribuidor'));
        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Guardar y Continuar Editando'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            -100
        );
        $this->buttonList->update('delete', 'label', __('Eliminar Distribuidor'));
    }

    public function getHeaderText()
    {
        $id = $this->getRequest()->getParam('dealer_id');
        if ($id) {
            $dealer = $this->dealerFactory->create();
            $dealer->load($id);
            return __("Editar Distribuidor: '%1'", $dealer->getData('name'));
        }
        return __('Nuevo Distribuidor');
    }
}
