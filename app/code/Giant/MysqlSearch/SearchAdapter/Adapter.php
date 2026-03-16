<?php
namespace Giant\MysqlSearch\SearchAdapter;

use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Framework\Api\Search\DocumentFactory;
use Magento\Framework\Search\Response\AggregationFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Search\Response\Bucket;
use Magento\Framework\Search\Response\Aggregation\Value;

class Adapter implements AdapterInterface
{
    private $resource;
    private $documentFactory;
    private $aggregationFactory;
    private $attributeValueFactory;

    public function __construct(
        ResourceConnection $resource,
        DocumentFactory $documentFactory,
        AggregationFactory $aggregationFactory,
        AttributeValueFactory $attributeValueFactory
    ) {
        $this->resource = $resource;
        $this->documentFactory = $documentFactory;
        $this->aggregationFactory = $aggregationFactory;
        $this->attributeValueFactory = $attributeValueFactory;
    }

    public function query(RequestInterface $request): QueryResponse
    {
        $documents = [];
        $connection = $this->resource->getConnection();

        $dimensions = $request->getDimensions();
        $storeId = 1;
        if (isset($dimensions['scope'])) {
            $storeId = (int)$dimensions['scope']->getValue();
        }

        $queryText = '';
        if ($request->getQuery() && method_exists($request->getQuery(), 'getValue')) {
            $queryText = trim((string)$request->getQuery()->getValue());
        }

        $categoryId = null;
        $this->extractFiltersFromRequest($request, $categoryId);

        $from = 0;
        $size = 10000;
        if (method_exists($request, 'getFrom')) {
            $from = (int)$request->getFrom();
        }
        if (method_exists($request, 'getSize')) {
            $reqSize = $request->getSize();
            if ($reqSize && $reqSize > 0) {
                $size = (int)$reqSize;
            }
        }

        $baseSelect = $connection->select();
        $indexTable = $this->resource->getTableName('catalog_category_product_index_store' . $storeId);

        if ($categoryId) {
            $baseSelect->from(['ccpi' => $indexTable], ['product_id' => 'ccpi.product_id'])
                ->where('ccpi.category_id = ?', (int)$categoryId)
                ->where('ccpi.store_id = ?', $storeId);

            if ($queryText !== '') {
                $this->addSearchFilter($baseSelect, $connection, $queryText, 'ccpi.product_id');
            }

            $baseSelect->group('ccpi.product_id');
            $baseSelect->order('ccpi.position ASC');
        } else {
            $baseSelect->from(['cpe' => $this->resource->getTableName('catalog_product_entity')], ['product_id' => 'cpe.entity_id']);

            $visAttrId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                    ->where('attribute_code = ?', 'visibility')
                    ->where('entity_type_id = ?', 4)
            );
            $baseSelect->join(
                ['cpei_vis' => $this->resource->getTableName('catalog_product_entity_int')],
                "cpe.entity_id = cpei_vis.entity_id AND cpei_vis.attribute_id = {$visAttrId} AND cpei_vis.store_id = 0 AND cpei_vis.value IN (2,3,4)",
                []
            );

            $statusAttrId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                    ->where('attribute_code = ?', 'status')
                    ->where('entity_type_id = ?', 4)
            );
            $baseSelect->join(
                ['cpei_status' => $this->resource->getTableName('catalog_product_entity_int')],
                "cpe.entity_id = cpei_status.entity_id AND cpei_status.attribute_id = {$statusAttrId} AND cpei_status.store_id = 0 AND cpei_status.value = 1",
                []
            );

            if ($queryText !== '') {
                $this->addSearchFilter($baseSelect, $connection, $queryText, 'cpe.entity_id');
            }

            $baseSelect->group('cpe.entity_id');
        }

        $allProductIds = [];
        try {
            $allProductIds = $connection->fetchCol($baseSelect);
        } catch (\Exception $e) {
            $allProductIds = [];
        }

        $paginatedIds = array_slice($allProductIds, $from, $size);

        $score = count($paginatedIds) + $from;
        foreach ($paginatedIds as $productId) {
            $scoreAttr = $this->attributeValueFactory->create();
            $scoreAttr->setAttributeCode('score');
            $scoreAttr->setValue($score--);

            $doc = new \Magento\Framework\Api\Search\Document([
                'id' => $productId,
                'custom_attributes' => ['score' => $scoreAttr]
            ]);
            $documents[] = $doc;
        }

        $buckets = $this->buildAggregations($request, $allProductIds, $connection, $storeId);
        $aggregations = $this->aggregationFactory->create(['buckets' => $buckets]);

        return new \Magento\Framework\Search\Response\QueryResponse(
            $documents,
            $aggregations,
            count($allProductIds)
        );
    }

    private function buildAggregations(RequestInterface $request, array $productIds, $connection, $storeId)
    {
        $buckets = [];

        if (empty($productIds)) {
            return $buckets;
        }

        $requestBuckets = [];
        try {
            $requestBuckets = $request->getAggregation();
        } catch (\Exception $e) {
            return $buckets;
        }

        if (empty($requestBuckets)) {
            return $buckets;
        }

        $productEntityTypeId = 4;
        $eavAttributeTable = $this->resource->getTableName('eav_attribute');

        foreach ($requestBuckets as $requestBucket) {
            $bucketName = $requestBucket->getName();
            $bucketField = $requestBucket->getField();

            try {
                if ($bucketField === 'category_ids') {
                    $values = $this->buildCategoryAggregation($productIds, $connection, $storeId);
                    $buckets[$bucketName] = new Bucket($bucketName, $values);
                } elseif ($bucketField === 'price') {
                    $values = $this->buildPriceAggregation($productIds, $connection, $storeId);
                    $buckets[$bucketName] = new Bucket($bucketName, $values);
                } else {
                    $row = $connection->fetchRow(
                        $connection->select()
                            ->from($eavAttributeTable, ['attribute_id', 'backend_type', 'frontend_input'])
                            ->where('attribute_code = ?', $bucketField)
                            ->where('entity_type_id = ?', $productEntityTypeId)
                    );

                    if (!$row || !$row['attribute_id']) {
                        continue;
                    }

                    $attrId = (int)$row['attribute_id'];
                    $backendType = $row['backend_type'];
                    $frontendInput = $row['frontend_input'];

                    $values = $this->buildAttributeAggregation(
                        $productIds,
                        $connection,
                        $attrId,
                        $backendType,
                        $frontendInput,
                        $storeId
                    );
                    $buckets[$bucketName] = new Bucket($bucketName, $values);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $buckets;
    }

    private function buildAttributeAggregation(
        array $productIds,
        $connection,
        int $attrId,
        string $backendType,
        string $frontendInput,
        int $storeId
    ) {
        $values = [];

        $eavTable = $this->resource->getTableName('catalog_product_entity_' . $backendType);

        if (!$connection->isTableExists($eavTable)) {
            return $values;
        }

        $isMultiselect = ($frontendInput === 'multiselect');

        $chunks = array_chunk($productIds, 5000);
        $allCounts = [];

        foreach ($chunks as $chunk) {
            if ($isMultiselect) {
                $select = $connection->select()
                    ->from(
                        ['eav_store' => $eavTable],
                        ['entity_id', 'value']
                    )
                    ->where('eav_store.attribute_id = ?', $attrId)
                    ->where('eav_store.entity_id IN (?)', $chunk)
                    ->where('eav_store.store_id = ?', $storeId)
                    ->where('eav_store.value IS NOT NULL');

                $selectDefault = $connection->select()
                    ->from(
                        ['eav_def' => $eavTable],
                        ['entity_id', 'value']
                    )
                    ->where('eav_def.attribute_id = ?', $attrId)
                    ->where('eav_def.entity_id IN (?)', $chunk)
                    ->where('eav_def.store_id = 0')
                    ->where('eav_def.value IS NOT NULL')
                    ->where('eav_def.entity_id NOT IN (?)',
                        $connection->select()
                            ->from($eavTable, ['entity_id'])
                            ->where('attribute_id = ?', $attrId)
                            ->where('store_id = ?', $storeId)
                            ->where('entity_id IN (?)', $chunk)
                    );

                $rows = array_merge(
                    $connection->fetchAll($select),
                    $connection->fetchAll($selectDefault)
                );

                foreach ($rows as $row) {
                    $csvValues = explode(',', (string)$row['value']);
                    foreach ($csvValues as $v) {
                        $v = trim($v);
                        if ($v === '') continue;
                        if (!isset($allCounts[$v])) {
                            $allCounts[$v] = 0;
                        }
                        $allCounts[$v]++;
                    }
                }
            } else {
                $select = $connection->select()
                    ->from(
                        ['eav_main' => $eavTable],
                        [
                            'option_value' => new \Zend_Db_Expr(
                                'COALESCE(eav_store.value, eav_main.value)'
                            ),
                            'cnt' => new \Zend_Db_Expr('COUNT(DISTINCT eav_main.entity_id)')
                        ]
                    )
                    ->joinLeft(
                        ['eav_store' => $eavTable],
                        "eav_main.entity_id = eav_store.entity_id AND eav_store.attribute_id = {$attrId} AND eav_store.store_id = {$storeId}",
                        []
                    )
                    ->where('eav_main.attribute_id = ?', $attrId)
                    ->where('eav_main.entity_id IN (?)', $chunk)
                    ->where('eav_main.store_id = 0')
                    ->group('option_value');

                $rows = $connection->fetchPairs($select);
                foreach ($rows as $val => $cnt) {
                    if ($val === '' || $val === null) continue;
                    if (!isset($allCounts[$val])) {
                        $allCounts[$val] = 0;
                    }
                    $allCounts[$val] += (int)$cnt;
                }
            }
        }

        foreach ($allCounts as $optionValue => $count) {
            if ($count > 0) {
                $values[] = new Value(
                    $optionValue,
                    ['value' => $optionValue, 'count' => $count]
                );
            }
        }

        return $values;
    }

    private function buildCategoryAggregation(array $productIds, $connection, int $storeId)
    {
        $values = [];

        if (empty($productIds)) {
            return $values;
        }

        $chunks = array_chunk($productIds, 5000);
        $allCounts = [];

        $indexTable = $this->resource->getTableName('catalog_category_product_index_store' . $storeId);

        foreach ($chunks as $chunk) {
            $select = $connection->select()
                ->from($indexTable, [
                    'category_id',
                    'cnt' => new \Zend_Db_Expr('COUNT(DISTINCT product_id)')
                ])
                ->where('product_id IN (?)', $chunk)
                ->where('store_id = ?', $storeId)
                ->group('category_id');

            $rows = $connection->fetchPairs($select);
            foreach ($rows as $catId => $cnt) {
                if (!isset($allCounts[$catId])) {
                    $allCounts[$catId] = 0;
                }
                $allCounts[$catId] += (int)$cnt;
            }
        }

        foreach ($allCounts as $catId => $count) {
            $values[] = new Value(
                $catId,
                ['value' => $catId, 'count' => $count]
            );
        }

        return $values;
    }

    private function buildPriceAggregation(array $productIds, $connection, int $storeId)
    {
        $values = [];

        if (empty($productIds)) {
            return $values;
        }

        $priceIndexTable = $this->resource->getTableName('catalog_product_index_price');

        $chunks = array_chunk($productIds, 5000);
        $allPrices = [];

        foreach ($chunks as $chunk) {
            $select = $connection->select()
                ->from($priceIndexTable, ['min_price'])
                ->where('entity_id IN (?)', $chunk)
                ->where('website_id = ?', 1)
                ->where('customer_group_id = ?', 0);

            $prices = $connection->fetchCol($select);
            $allPrices = array_merge($allPrices, $prices);
        }

        $allPrices = array_filter($allPrices, function ($p) {
            return $p !== null && $p !== '' && (float)$p > 0;
        });
        $allPrices = array_map('floatval', $allPrices);

        if (empty($allPrices)) {
            return $values;
        }

        sort($allPrices);
        $minPrice = $allPrices[0];
        $maxPrice = end($allPrices);
        $totalCount = count($allPrices);

        if ($maxPrice <= $minPrice) {
            $values[] = new Value(
                (string)$minPrice . '_' . (string)($maxPrice + 1),
                ['value' => (string)$minPrice . '_' . (string)($maxPrice + 1), 'count' => $totalCount]
            );
            return $values;
        }

        $rangeStep = 10;
        $range = $maxPrice - $minPrice;
        if ($range > 10000) {
            $rangeStep = 5000000;
        } elseif ($range > 1000) {
            $rangeStep = 1000000;
        } elseif ($range > 100) {
            $rangeStep = 500000;
        } else {
            $rangeStep = 100000;
        }

        $rangeCounts = [];
        foreach ($allPrices as $price) {
            $rangeFrom = floor($price / $rangeStep) * $rangeStep;
            $rangeTo = $rangeFrom + $rangeStep;
            $key = (string)$rangeFrom . '_' . (string)$rangeTo;
            if (!isset($rangeCounts[$key])) {
                $rangeCounts[$key] = 0;
            }
            $rangeCounts[$key]++;
        }

        foreach ($rangeCounts as $rangeKey => $count) {
            $values[] = new Value(
                $rangeKey,
                ['value' => $rangeKey, 'count' => $count]
            );
        }

        return $values;
    }

    private function addSearchFilter($select, $connection, $queryText, $entityColumn)
    {
        $nameAttrId = (int)$connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', 'name')
                ->where('entity_type_id = ?', 4)
        );
        $eavTable = $this->resource->getTableName('catalog_product_entity_varchar');
        $select->joinLeft(
            ['pname' => $eavTable],
            "{$entityColumn} = pname.entity_id AND pname.attribute_id = {$nameAttrId} AND pname.store_id = 0",
            []
        );
        $likePattern = '%' . $queryText . '%';
        $select->where('pname.value LIKE ? OR ' . $entityColumn . ' IN (SELECT entity_id FROM ' . $this->resource->getTableName('catalog_product_entity') . ' WHERE sku LIKE ?)', $likePattern);
    }

    private function extractFiltersFromRequest($request, &$categoryId)
    {
        $query = $request->getQuery();
        if (!$query) return;

        $this->walkQueryTree($query, $categoryId);
    }

    private function walkQueryTree($query, &$categoryId)
    {
        if (!$query) return;

        if ($query instanceof \Magento\Framework\Search\Request\Query\Filter) {
            $ref = $query->getReference();
            if ($ref instanceof \Magento\Framework\Search\Request\Filter\Term) {
                if ($ref->getField() === 'category_ids') {
                    $categoryId = (int)$ref->getValue();
                }
            } elseif ($ref instanceof \Magento\Framework\Search\Request\Filter\Range) {
            } elseif ($ref instanceof \Magento\Framework\Search\Request\Filter\BoolExpression) {
                $this->walkFilterTree($ref, $categoryId);
            }
        }

        if ($query instanceof \Magento\Framework\Search\Request\Query\BoolExpression) {
            foreach (['getMust', 'getShould'] as $method) {
                if (method_exists($query, $method)) {
                    foreach ($query->$method() as $subQuery) {
                        $this->walkQueryTree($subQuery, $categoryId);
                    }
                }
            }
        }
    }

    private function walkFilterTree($filter, &$categoryId)
    {
        if ($filter instanceof \Magento\Framework\Search\Request\Filter\Term) {
            if ($filter->getField() === 'category_ids') {
                $categoryId = (int)$filter->getValue();
            }
        } elseif ($filter instanceof \Magento\Framework\Search\Request\Filter\BoolExpression) {
            foreach (['getMust', 'getShould'] as $method) {
                if (method_exists($filter, $method)) {
                    foreach ($filter->$method() as $subFilter) {
                        $this->walkFilterTree($subFilter, $categoryId);
                    }
                }
            }
        }
    }
}
