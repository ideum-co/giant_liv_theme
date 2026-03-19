<?php
namespace Giant\SheetSync\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Giant\SheetSync\Helper\GoogleSheet;
use Giant\SheetSync\Helper\ProductSync;

class Run extends Action
{
    const ADMIN_RESOURCE = 'Giant_SheetSync::sync';

    protected $jsonFactory;
    protected $googleSheet;
    protected $productSync;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        GoogleSheet $googleSheet,
        ProductSync $productSync
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->googleSheet = $googleSheet;
        $this->productSync = $productSync;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $rows = $this->googleSheet->readSheet();

            if (empty($rows) || count($rows) < 2) {
                return $result->setData([
                    'success' => false,
                    'message' => 'La hoja de Google Sheets está vacía o no tiene datos.'
                ]);
            }

            $syncResults = $this->productSync->syncProducts($rows);

            if (!empty($syncResults['not_found_skus'])) {
                $this->googleSheet->writeNotFoundSkus($syncResults['not_found_skus']);
            }

            return $result->setData([
                'success' => true,
                'message' => 'Sincronización completada exitosamente.',
                'data' => [
                    'total' => $syncResults['total'],
                    'updated' => $syncResults['updated'],
                    'not_found' => $syncResults['not_found'],
                    'errors' => $syncResults['errors'],
                    'error_details' => array_slice($syncResults['error_details'], 0, 20),
                    'sample_updated' => array_slice($syncResults['updated_skus'], 0, 10),
                    'sample_not_found' => array_slice(array_column($syncResults['not_found_skus'], 'sku'), 0, 10)
                ]
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
