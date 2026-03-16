<?php
namespace Giant\Dealers\Block\Adminhtml\Dealer\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('dealer_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Distribuidor'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('main_section', [
            'label' => __('Información General'),
            'content' => $this->getLayout()->createBlock(
                \Giant\Dealers\Block\Adminhtml\Dealer\Edit\Tab\Main::class
            )->toHtml(),
            'active' => true
        ]);
        return parent::_beforeToHtml();
    }
}
