<?php
namespace Giant\BikeRegistration\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Giant\BikeRegistration\Model\RegistrationFactory;
use Giant\BikeRegistration\Model\Pdf\PropertyCard;

class Download extends Action
{
    protected $fileFactory;
    protected $registrationFactory;
    protected $propertyCard;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        RegistrationFactory $registrationFactory,
        PropertyCard $propertyCard
    ) {
        $this->fileFactory = $fileFactory;
        $this->registrationFactory = $registrationFactory;
        $this->propertyCard = $propertyCard;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $token = $this->getRequest()->getParam('token');

        if (!$id || !$token) {
            $this->messageManager->addErrorMessage(__('Enlace de descarga inválido.'));
            return $this->_redirect('bikeregistration');
        }

        $registration = $this->registrationFactory->create();
        $registration->load($id);

        if (!$registration->getId()) {
            $this->messageManager->addErrorMessage(__('Registro no encontrado.'));
            return $this->_redirect('bikeregistration');
        }

        if (!hash_equals((string)$registration->getData('download_token'), (string)$token)) {
            $this->messageManager->addErrorMessage(__('Enlace de descarga inválido.'));
            return $this->_redirect('bikeregistration');
        }

        $pdfContent = $this->propertyCard->generate($registration);
        $fileName = 'tarjeta_propiedad_' . $registration->getSerialNumber() . '.pdf';

        return $this->fileFactory->create(
            $fileName,
            $pdfContent,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }
}
