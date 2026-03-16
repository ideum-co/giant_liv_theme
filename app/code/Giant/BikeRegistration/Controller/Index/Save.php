<?php
namespace Giant\BikeRegistration\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Giant\BikeRegistration\Model\RegistrationFactory;
use Psr\Log\LoggerInterface;

class Save extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $registrationFactory;
    protected $filesystem;
    protected $formKeyValidator;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RegistrationFactory $registrationFactory,
        Filesystem $filesystem,
        FormKeyValidator $formKeyValidator,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->registrationFactory = $registrationFactory;
        $this->filesystem = $filesystem;
        $this->formKeyValidator = $formKeyValidator;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->jsonFactory->create();
        $result->setData(['success' => false, 'message' => 'Solicitud inválida. Recargue la página e intente de nuevo.']);
        return new InvalidRequestException($result);
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $result->setData(['success' => false, 'message' => 'Método de solicitud inválido.']);
        }

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData(['success' => false, 'message' => 'Solicitud inválida. Recargue la página e intente de nuevo.']);
        }

        $post = $this->getRequest()->getPostValue();

        $requiredFields = [
            'full_name', 'document_type', 'document_number', 'phone', 'email',
            'department_city', 'store_name', 'purchase_city', 'purchase_date',
            'bike_reference', 'serial_number', 'amount_paid', 'invoice_number'
        ];

        foreach ($requiredFields as $field) {
            if (empty($post[$field])) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Por favor complete todos los campos obligatorios.'
                ]);
            }
        }

        if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
            return $result->setData([
                'success' => false,
                'message' => 'Por favor ingrese un email válido.'
            ]);
        }

        try {
            $invoicePath = null;
            $files = $this->getRequest()->getFiles();
            if ($files && isset($files['invoice_file']) && $files['invoice_file']['error'] === UPLOAD_ERR_OK) {
                $invoicePath = $this->handleFileUpload($files['invoice_file']);
            }

            $amountPaid = str_replace(['$', '.', ' '], '', $post['amount_paid']);
            $amountPaid = str_replace(',', '.', $amountPaid);

            $downloadToken = bin2hex(random_bytes(16));

            $registration = $this->registrationFactory->create();
            $registration->setData([
                'full_name' => trim($post['full_name']),
                'document_type' => $post['document_type'],
                'document_number' => trim($post['document_number']),
                'address' => isset($post['address']) ? trim($post['address']) : null,
                'gender' => isset($post['gender']) ? $post['gender'] : null,
                'phone' => trim($post['phone']),
                'email' => trim($post['email']),
                'birthday' => !empty($post['birthday']) ? $post['birthday'] : null,
                'department_city' => trim($post['department_city']),
                'store_name' => trim($post['store_name']),
                'purchase_city' => trim($post['purchase_city']),
                'purchase_date' => $post['purchase_date'],
                'bike_reference' => trim($post['bike_reference']),
                'serial_number' => trim($post['serial_number']),
                'amount_paid' => floatval($amountPaid),
                'invoice_number' => trim($post['invoice_number']),
                'invoice_file' => $invoicePath,
                'status' => 'registered',
                'download_token' => $downloadToken
            ]);
            $registration->save();

            return $result->setData([
                'success' => true,
                'message' => '¡Tu bicicleta ha sido registrada exitosamente!',
                'registration_id' => $registration->getId(),
                'download_token' => $downloadToken
            ]);
        } catch (\Exception $e) {
            $this->logger->error('BikeRegistration save error: ' . $e->getMessage(), ['exception' => $e]);
            return $result->setData([
                'success' => false,
                'message' => 'Ha ocurrido un error al procesar el registro. Por favor intente de nuevo.'
            ]);
        }
    }

    protected function handleFileUpload($file)
    {
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'tif', 'tiff'];
        $maxSize = 10 * 1024 * 1024;

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Tipo de archivo no permitido. Use: PDF, DOC, JPG, PNG o TIF.');
        }

        if ($file['size'] > $maxSize) {
            throw new \Exception('El archivo excede el tamaño máximo de 10MB.');
        }

        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $targetDir = 'bike_registration/invoices';
        $mediaDir->create($targetDir);

        $newFileName = bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $newFileName;

        $mediaDir->getDriver()->copy($file['tmp_name'], $mediaDir->getAbsolutePath($targetPath));

        return $targetPath;
    }
}
