<?php
namespace Giant\SheetSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

class ProductSync extends AbstractHelper
{
    protected $resource;

    public function __construct(Context $context, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function syncProducts(array $rows)
    {
        $connection = $this->resource->getConnection();

        $header = array_shift($rows);
        $colMap = array_flip($header);

        $skuIdx = $colMap['sku'] ?? 0;
        $priceIdx = $colMap['price'] ?? 1;
        $specialPriceIdx = $colMap['special_price'] ?? 2;
        $qtyIdx = $colMap['qty'] ?? 3;
        $fromDateIdx = $colMap['special_price_from_date'] ?? 4;
        $toDateIdx = $colMap['special_price_to_date'] ?? 5;

        $results = [
            'total' => count($rows),
            'updated' => 0,
            'not_found' => 0,
            'errors' => 0,
            'not_found_skus' => [],
            'error_details' => [],
            'updated_skus' => []
        ];

        $priceAttrId = $this->getAttributeId($connection, 'price');
        $specialPriceAttrId = $this->getAttributeId($connection, 'special_price');
        $specialFromAttrId = $this->getAttributeId($connection, 'special_from_date');
        $specialToAttrId = $this->getAttributeId($connection, 'special_to_date');

        foreach ($rows as $row) {
            $sku = trim($row[$skuIdx] ?? '');
            if (empty($sku)) continue;

            $price = $row[$priceIdx] ?? '';
            $specialPrice = $row[$specialPriceIdx] ?? '';
            $qty = $row[$qtyIdx] ?? '';
            $fromDate = $row[$fromDateIdx] ?? '';
            $toDate = $row[$toDateIdx] ?? '';

            try {
                $entityId = $connection->fetchOne(
                    "SELECT entity_id FROM catalog_product_entity WHERE sku = ?",
                    [$sku]
                );

                if (!$entityId) {
                    $results['not_found']++;
                    $results['not_found_skus'][] = [
                        'sku' => $sku,
                        'price' => $price,
                        'special_price' => $specialPrice,
                        'qty' => $qty,
                        'from_date' => $fromDate,
                        'to_date' => $toDate
                    ];
                    continue;
                }

                if ($price !== '') {
                    $this->updateDecimalAttribute($connection, $entityId, $priceAttrId, (float)$price);
                }

                if ($specialPrice !== '') {
                    $this->updateDecimalAttribute($connection, $entityId, $specialPriceAttrId, (float)$specialPrice);
                }

                if ($fromDate !== '') {
                    $parsedFrom = $this->parseDate($fromDate);
                    if ($parsedFrom) {
                        $this->updateDatetimeAttribute($connection, $entityId, $specialFromAttrId, $parsedFrom);
                    }
                }

                if ($toDate !== '') {
                    $parsedTo = $this->parseDate($toDate);
                    if ($parsedTo) {
                        $this->updateDatetimeAttribute($connection, $entityId, $specialToAttrId, $parsedTo);
                    }
                }

                if ($qty !== '') {
                    $this->updateStock($connection, $entityId, (int)$qty);
                }

                $results['updated']++;
                $results['updated_skus'][] = $sku;

            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_details'][] = $sku . ': ' . $e->getMessage();
            }
        }

        return $results;
    }

    protected function getAttributeId($connection, $attributeCode)
    {
        return $connection->fetchOne(
            "SELECT attribute_id FROM eav_attribute WHERE attribute_code = ? AND entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_product')",
            [$attributeCode]
        );
    }

    protected function updateDecimalAttribute($connection, $entityId, $attributeId, $value)
    {
        $table = $this->resource->getTableName('catalog_product_entity_decimal');
        $exists = $connection->fetchOne(
            "SELECT value_id FROM {$table} WHERE entity_id = ? AND attribute_id = ? AND store_id = 0",
            [$entityId, $attributeId]
        );

        if ($exists) {
            $connection->update($table, ['value' => $value], [
                'entity_id = ?' => $entityId,
                'attribute_id = ?' => $attributeId,
                'store_id = ?' => 0
            ]);
        } else {
            $connection->insert($table, [
                'attribute_id' => $attributeId,
                'store_id' => 0,
                'entity_id' => $entityId,
                'value' => $value
            ]);
        }
    }

    protected function updateDatetimeAttribute($connection, $entityId, $attributeId, $value)
    {
        $table = $this->resource->getTableName('catalog_product_entity_datetime');
        $exists = $connection->fetchOne(
            "SELECT value_id FROM {$table} WHERE entity_id = ? AND attribute_id = ? AND store_id = 0",
            [$entityId, $attributeId]
        );

        if ($exists) {
            $connection->update($table, ['value' => $value], [
                'entity_id = ?' => $entityId,
                'attribute_id = ?' => $attributeId,
                'store_id = ?' => 0
            ]);
        } else {
            $connection->insert($table, [
                'attribute_id' => $attributeId,
                'store_id' => 0,
                'entity_id' => $entityId,
                'value' => $value
            ]);
        }
    }

    protected function updateStock($connection, $entityId, $qty)
    {
        $stockTable = $this->resource->getTableName('cataloginventory_stock_item');
        $isInStock = ($qty > 0) ? 1 : 0;

        $exists = $connection->fetchOne(
            "SELECT item_id FROM {$stockTable} WHERE product_id = ?",
            [$entityId]
        );

        if ($exists) {
            $connection->update($stockTable, [
                'qty' => $qty,
                'is_in_stock' => $isInStock
            ], ['product_id = ?' => $entityId]);
        } else {
            $connection->insert($stockTable, [
                'product_id' => $entityId,
                'stock_id' => 1,
                'qty' => $qty,
                'is_in_stock' => $isInStock
            ]);
        }

        $stockStatusTable = $this->resource->getTableName('cataloginventory_stock_status');
        $existsStatus = $connection->fetchOne(
            "SELECT product_id FROM {$stockStatusTable} WHERE product_id = ?",
            [$entityId]
        );

        if ($existsStatus) {
            $connection->update($stockStatusTable, [
                'qty' => $qty,
                'stock_status' => $isInStock
            ], ['product_id = ?' => $entityId]);
        }
    }

    protected function parseDate($dateStr)
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) return null;

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $dateStr, $m)) {
            return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . ' 00:00:00';
        }

        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $dateStr)) {
            return $dateStr . (strlen($dateStr) === 10 ? ' 00:00:00' : '');
        }

        return null;
    }
}
