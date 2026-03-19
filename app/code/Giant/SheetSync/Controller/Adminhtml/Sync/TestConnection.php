<?php
namespace Giant\SheetSync\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Giant\SheetSync\Helper\GoogleSheet;
use Magento\Framework\App\ResourceConnection;

class TestConnection extends Action
{
    const ADMIN_RESOURCE = 'Giant_SheetSync::sync';

    protected $jsonFactory;
    protected $googleSheet;
    protected $resource;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        GoogleSheet $googleSheet,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->googleSheet = $googleSheet;
        $this->resource = $resource;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $checks = [];

        try {
            $this->googleSheet->getAccessToken();
            $checks['google_auth'] = ['status' => 'ok', 'message' => 'Autenticación con Google OK'];
        } catch (\Exception $e) {
            $checks['google_auth'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            $rows = $this->googleSheet->readSheet();
            $rowCount = count($rows) - 1;
            $checks['google_sheet'] = ['status' => 'ok', 'message' => "Google Sheet leído correctamente ({$rowCount} productos)"];
        } catch (\Exception $e) {
            $checks['google_sheet'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            $connection = $this->resource->getConnection();
            $productCount = $connection->fetchOne("SELECT COUNT(*) FROM catalog_product_entity");
            $checks['magento_db'] = ['status' => 'ok', 'message' => "Base de datos Magento OK ({$productCount} productos)"];
        } catch (\Exception $e) {
            $checks['magento_db'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        $allOk = true;
        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $allOk = false;
                break;
            }
        }

        return $result->setData([
            'success' => $allOk,
            'checks' => $checks,
            'message' => $allOk ? 'Todas las conexiones están funcionando correctamente.' : 'Hay problemas con algunas conexiones.'
        ]);
    }
}
