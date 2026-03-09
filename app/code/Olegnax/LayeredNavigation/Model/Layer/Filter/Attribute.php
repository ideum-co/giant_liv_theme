<?php
/**
 * @author      Olegnax
 * @package     Olegnax_LayeredNavigation
 * @copyright   Copyright (c) 2019 Olegnax (http://olegnax.com/). All rights reserved.
 * @license     Proprietary License https://olegnax.com/license/
 */

namespace Olegnax\LayeredNavigation\Model\Layer\Filter;

use Amasty\ShopbyBase\Helper\FilterSetting;
use Exception;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Framework\Search\ResponseInterface;
use Magento\Search\Model\SearchEngine;
use Magento\Store\Model\StoreManagerInterface;
use Olegnax\LayeredNavigation\Helper\Helper;
use Olegnax\LayeredNavigation\Model\Request\Builder;
use Olegnax\LayeredNavigation\Model\ResourceModel\Fulltext\Collection;
use Psr\Log\LoggerInterface;

class Attribute extends \Magento\CatalogSearch\Model\Layer\Filter\Attribute
{

    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var bool
     */
    protected $_isFilter = false;
    /**
     * @var SearchEngine
     */
    protected $searchEngine;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var array
     */
    protected $attributeValues = [];
    /**
     * @var StripTags
     */
    private $tagFilter;

    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        Helper $helper,
        SearchEngine $searchEngine,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $data
        );
        $this->_helper = $helper;
        $this->searchEngine = $searchEngine;
        $this->messageManager = $messageManager;
        $this->tagFilter = $tagFilter;
    }

    /**
     * Apply filter to collection
     *
     * @param RequestInterface $request
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function apply(RequestInterface $request)
    {
        if (!$this->pluginEnable()) {
            return parent::apply($request);
        }

        $attributeValue = $request->getParam($this->getRequestVar());
        if (empty($attributeValue)) {
            return $this;
        }

        $attributeValue = explode(',', $attributeValue);
        $this->setCurrentValue($attributeValue);

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->getAttributeModel();
        $id = $attribute->getAttributeId();

        if (!$this->isMultiselect($id) && count($attributeValue) > 1) {
            $attributeValue = array_slice($attributeValue, 0, 1);
        }
        /** @var Collection $productCollection */
        $productCollection = $this->getProductCollection();

        $productCollection->addFieldToFilter($attribute->getAttributeCode(), $attributeValue);

        if ($this->shouldAddState($id)) {
            $this->addState($attributeValue);
        }
        if (!$this->isVisibleWhenSelected($id)) {
            $this->setItems([]); // set items to disable show filtering
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function pluginEnable()
    {
        return (bool)$this->_helper->isEnabled();
    }

    /**
     * @param array $attributeValues
     */
    protected function setCurrentValue(array $attributeValues)
    {
        $this->attributeValues = $attributeValues;
    }

    /**
     * @param $id
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    protected function isMultiselect($id)
    {
        return true;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection()
    {
        return $this->getLayer()->getProductCollection();
    }

    /**
     * @param $id
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function shouldAddState($id)
    {
        return !$this->isBrandingBrand();
    }

    /**
     * @return bool
     */
    protected function isBrandingBrand()
    {
        if (
            class_exists('Amasty\ShopbyBrand\Helper\Content')
            && $contentHelper = ObjectManager::getInstance()->get('Amasty\ShopbyBrand\Helper\Content')
        ) {
            $brand = $contentHelper->getCurrentBranding();
            return $brand &&
                (FilterSetting::ATTR_PREFIX . $this->getRequestVar() ==
                    $brand->getFilterCode());
        }

        return false;
    }

    /**
     * @param $values
     * @throws LocalizedException
     */
    protected function addState($values)
    {
        $labels = [];
        foreach ((array)$values as $value) {
            $label = $this->getOptionText($value);
            $labels[$value] = $label;
        }
        foreach ($labels as $id => $label) {
            $item = $this->_createItem($label, $id);
            $this->getLayer()->getState()
                ->addFilter(
                    $item
                );
        }
    }

    /**
     * @param $id
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function isVisibleWhenSelected($id)
    {
        return !$this->isBrandingBrand();
    }

    /**
     * @param $a
     * @param $b
     * @return int|mixed
     */
    public function sortOption($a, $b)
    {
        $pattern = '@^(\d+)@';
        if (preg_match($pattern, $a['label'], $ma) && preg_match($pattern, $b['label'], $mb)) {
            $r = $ma[1] - $mb[1];
            if ($r != 0) {
                return $r;
            }
        }

        return strcasecmp($a['label'], $b['label']);
    }

    /**
     * Get data array for building filter items
     *
     * Result array should have next structure:
     * array(
     *      $index => array(
     *          'label' => $label,
     *          'value' => $value,
     *          'count' => $count
     *      )
     * )
     *
     * @return array
     * @throws LocalizedException
     */
    protected function _getItemsData()
    {
        if (!$this->pluginEnable()) {
            return parent::_getItemsData();
        }
        $attribute = $this->getAttributeModel();
        if ($this->hasCurrentValue() && !$this->isVisibleWhenSelected($attribute->getAttributeId())) {
            return [];
        }

        $options = $this->getOptions();
        $optionsFacetedData = $this->getOptionsFacetedData();
        if (!$optionsFacetedData) {
            return [];
        }

        $this->addItemsToDataBuilder($options, $optionsFacetedData);
        $itemsData = $this->getItemsFromDataBuilder();

        return $itemsData;

    }

    /**
     * @return bool
     */
    protected function hasCurrentValue()
    {
        return !empty($this->attributeValues);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function getOptions()
    {
        $attribute = $this->getAttributeModel();
        $options = $attribute->getFrontend()->getSelectOptions();

        usort($options, [$this, 'sortOption']);

        return $options;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function getOptionsFacetedData()
    {
        $optionsFacetedData = $this->generateOptionsFacetedData();

        if (count($optionsFacetedData)) {
            $optionsFacetedData = $this->convertOptionsFacetedData($optionsFacetedData);
        }

        return $optionsFacetedData;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function generateOptionsFacetedData()
    {
        $productCollection = $this->getProductCollection();
        $attribute = $this->getAttributeModel();
        $attributeCode = $attribute->getAttributeCode();
        try {
            $optionsFacetedData = $productCollection->getFacetedData(
                $attributeCode,
                $this->getAlteredQueryResponse()
            );
        } catch (StateException $e) {
            if (!$this->messageManager->hasMessages()) {
                $this->messageManager->addErrorMessage(
                    __('Make sure that "%1" attribute can be used in layered navigation', $attributeCode)
                );
            }
            $optionsFacetedData = [];
        }

        return $optionsFacetedData;
    }

    /**
     * @return QueryResponse|ResponseInterface|null
     */
    protected function getAlteredQueryResponse()
    {
        $alteredQueryResponse = null;
        if ($this->hasCurrentValue()) {
            try {
                $requestBuilder = $this->getRequestBuilder();
                if (!$requestBuilder) {
                    return $alteredQueryResponse;
                }
                $queryRequest = $requestBuilder->create();
                $alteredQueryResponse = $this->searchEngine->search($queryRequest);
            } catch (Exception $e) {
                $alteredQueryResponse = null;
            }
        }
        return $alteredQueryResponse;
    }

    /**
     * @return Builder|null
     * @throws LocalizedException
     */
    protected function getRequestBuilder()
    {
        $requestBuilder = $this->getMemRequestBuilder();
        if (!$requestBuilder) {
            return null;
        }
        $attributeCode = $this->getAttributeModel()->getAttributeCode();
        $requestBuilder->removePlaceholder($attributeCode);
        $requestBuilder->setAggregationsOnly($attributeCode);

        return $requestBuilder;
    }

    /**
     * @return Builder|null
     */
    private function getMemRequestBuilder()
    {
        $productCollection = $this->getProductCollection();
        if (method_exists($productCollection, 'getMemRequestBuilder')) {
            return clone $productCollection->getMemRequestBuilder();
        }
        $this->_helper->_loadObject(LoggerInterface::class)
            ->error(__('Invalid class for product collection: ') . get_class($productCollection));

        return null;
    }

    /**
     * @param array $optionsFacetedData
     * @return array
     */
    protected function convertOptionsFacetedData(array $optionsFacetedData)
    {
        $values = $this->getCurrentValue();
        foreach ($values as $value) {
            if (!empty($value) && !array_key_exists($value, $optionsFacetedData)) {
                $optionsFacetedData[$value] = [
                    'value' => $value,
                    'count' => 0,
                ];
            }
        }

        return $optionsFacetedData;
    }

    /**
     * @return array
     */
    protected function getCurrentValue()
    {
        return $this->attributeValues;
    }

    /**
     * @param array $options
     * @param array $optionsFacetedData
     * @throws LocalizedException
     */
    protected function addItemsToDataBuilder(array $options, array $optionsFacetedData)
    {
        if (!$options) {
            return;
        }
        foreach ($options as $option) {
            if (empty($option['value'])) {
                continue;
            }

            $isFilterableAttribute = $this->getAttributeIsFilterable($this->getAttributeModel());
            if (isset($optionsFacetedData[$option['value']])
                || $isFilterableAttribute != static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
            ) {
                $count = 0;
                if (isset($optionsFacetedData[$option['value']]['count'])) {
                    $count = $optionsFacetedData[$option['value']]['count'];
                }
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($option['label']),
                    $option['value'],
                    $count
                );
            }
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getItemsFromDataBuilder()
    {
        $itemsData = $this->itemDataBuilder->build();
        if (count($itemsData) == 1
            && !$this->isOptionReducesResults(
                $itemsData[0]['count'],
                $this->getLayer()->getProductCollection()->getSize()
            )
        ) {
            $itemsData = $this->getReducedItemsData($itemsData);
        }

        return $itemsData;
    }

    /**
     * @param array $itemsData
     * @return array
     * @throws LocalizedException
     */
    protected function getReducedItemsData(array $itemsData)
    {
        $isFilterActive = false;
        /** @var Item $filter */
        foreach ($this->getLayer()->getState()->getFilters() as $filter) {

            if ($filter->getFilter()->getRequestVar() == $this->getRequestVar()) {
                $isFilterActive = true;
                break;
            }
        }

        return $isFilterActive ? $itemsData : [];
    }
}