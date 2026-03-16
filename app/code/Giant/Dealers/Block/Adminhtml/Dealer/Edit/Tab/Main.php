<?php
namespace Giant\Dealers\Block\Adminhtml\Dealer\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Giant\Dealers\Model\DealerFactory;

class Main extends Generic implements TabInterface
{
    protected $dealerFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        DealerFactory $dealerFactory,
        array $data = []
    ) {
        $this->dealerFactory = $dealerFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $dealer = $this->dealerFactory->create();
        $id = $this->getRequest()->getParam('dealer_id');
        if ($id) {
            $dealer->load($id);
        }

        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Información del Distribuidor')]);

        if ($dealer->getId()) {
            $fieldset->addField('dealer_id', 'hidden', ['name' => 'dealer_id']);
        }

        $fieldset->addField('name', 'text', [
            'name' => 'name',
            'label' => __('Nombre'),
            'title' => __('Nombre'),
            'required' => true,
        ]);

        $fieldset->addField('city', 'text', [
            'name' => 'city',
            'label' => __('Ciudad'),
            'title' => __('Ciudad'),
            'required' => true,
        ]);

        $fieldset->addField('address', 'text', [
            'name' => 'address',
            'label' => __('Dirección'),
            'title' => __('Dirección'),
            'required' => true,
        ]);

        $fieldset->addField('phones', 'text', [
            'name' => 'phones',
            'label' => __('Teléfonos'),
            'title' => __('Teléfonos'),
        ]);

        $fieldset->addField('email', 'text', [
            'name' => 'email',
            'label' => __('Email de Contacto'),
            'title' => __('Email de Contacto'),
        ]);

        $fieldset->addField('logo', 'text', [
            'name' => 'logo',
            'label' => __('Ruta del Logo'),
            'title' => __('Ruta del Logo'),
            'note' => __('Ruta relativa en media/dealers/ (ej: dealers/logo.png). Suba la imagen vía FTP/SFTP a pub/media/dealers/'),
        ]);

        $fieldset->addField('latitude', 'text', [
            'name' => 'latitude',
            'label' => __('Latitud'),
            'title' => __('Latitud'),
            'note' => __('Ej: 4.710989'),
        ]);

        $fieldset->addField('longitude', 'text', [
            'name' => 'longitude',
            'label' => __('Longitud'),
            'title' => __('Longitud'),
            'note' => __('Ej: -74.072092'),
        ]);

        $fieldset->addField('is_active', 'select', [
            'name' => 'is_active',
            'label' => __('Activo'),
            'title' => __('Activo'),
            'values' => [
                ['value' => 1, 'label' => __('Sí')],
                ['value' => 0, 'label' => __('No')],
            ],
        ]);

        $fieldset->addField('sort_order', 'text', [
            'name' => 'sort_order',
            'label' => __('Orden'),
            'title' => __('Orden'),
            'note' => __('Número para ordenar en el listado'),
        ]);

        $form->setValues($dealer->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel() { return __('Información General'); }
    public function getTabTitle() { return __('Información General'); }
    public function canShowTab() { return true; }
    public function isHidden() { return false; }
}
