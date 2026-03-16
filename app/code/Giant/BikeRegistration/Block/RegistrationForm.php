<?php
namespace Giant\BikeRegistration\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Data\Form\FormKey;

class RegistrationForm extends Template
{
    protected $formKey;

    public function __construct(
        Context $context,
        FormKey $formKey,
        array $data = []
    ) {
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    public function getSaveUrl()
    {
        return $this->getUrl('bikeregistration/index/save');
    }

    public function getDownloadUrl()
    {
        return $this->getUrl('bikeregistration/index/download');
    }
}
