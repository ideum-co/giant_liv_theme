<?php
/**
 * @author      Olegnax
 * @package     Olegnax_LayeredNavigation
 * @copyright   Copyright (c) 2019 Olegnax (http://olegnax.com/). All rights reserved.
 * @license     Proprietary License https://olegnax.com/license/
 */

namespace Olegnax\LayeredNavigation\Model\Layer\Filter;

use Exception;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory;
use Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Search\Dynamic\Algorithm;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Framework\Search\ResponseInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Search\Model\SearchEngine;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Olegnax\LayeredNavigation\Helper\Helper;
use Olegnax\LayeredNavigation\Model\Request\Builder;
use Psr\Log\LoggerInterface;

class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{

    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var array
     */
    protected $attributeValues = [];
    /**
     * @var Registry
     */
    protected $coreRegistry;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var SearchEngine
     */
    protected $searchEngine;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Json
     */
    protected $json;
    /**
     * @var string
     */
    protected $currencySymbol;
    /**
     * @var Layer\Filter\DataProvider\Price
     */
    private $dataProvider;

    /**
     * Price constructor.
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource
     * @param Session $customerSession
     * @param Algorithm $priceAlgorithm
     * @param PriceCurrencyInterface $priceCurrency
     * @param AlgorithmFactory $algorithmFactory
     * @param PriceFactory $dataProviderFactory
     * @param Helper $helper
     * @param Registry $coreRegistry
     * @param SearchEngine $searchEngine
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param array $data
     * @noinspection PhpDeprecationInspection
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        Session $customerSession,
        Algorithm $priceAlgorithm,
        PriceCurrencyInterface $priceCurrency,
        AlgorithmFactory $algorithmFactory,
        PriceFactory $dataProviderFactory,
        Helper $helper,
        Registry $coreRegistry,
        SearchEngine $searchEngine,
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        Json $json,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $data
        );
        $this->_helper = $helper;
        $this->dataProvider = $dataProviderFactory->create(['layer' => $layer]);
        $this->coreRegistry = $coreRegistry;
        $this->searchEngine = $searchEngine;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * @param RequestInterface $request
     * @return $this|Price
     */
    public function apply(RequestInterface $request)
    {
        if (!$this->pluginEnable()) {
            return parent::apply($request);
        }

        $filter = $request->getParam($this->getRequestVar());
        if (empty($filter)) {
            return $this;
        }
        if (is_string($filter)) {
            $filter = explode(',', $filter);
        }
        $filterParams = $filter;

        $filter = $this->dataProvider->validateFilter($filterParams[0]);
        if (!$filter) {
            return $this;
        }

        $this->setCurrentValue($filter);
        $this->dataProvider->setInterval($filter);
        $priorFilters = $this->dataProvider->getPriorFilters($filterParams);
        if ($priorFilters) {
            $this->dataProvider->setPriorIntervals($priorFilters);
        }

        [$from, $to] = $filter;

        $this->getProductCollection()->addFieldToFilter(
            'price',
            [
                'from' => $from,
                'to' => $to,
            ]
        );
        if ($this->shouldAddState()) {
            $this->addState($filter);
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
     * @return Collection
     */
    protected function getProductCollection()
    {
        return $this->getLayer()->getProductCollection();
    }

    /**
     * @return bool
     */
    public function shouldAddState()
    {
        return true;
    }

    /**
     * @param $values
     */
    protected function addState($values)
    {
        [$from, $to] = $values;
        $label = $this->_renderRangeLabel(empty($from) ? 0 : $from, $to);
        $item = $this->_createItem($label, $values);
        $this->getLayer()->getState()
            ->addFilter(
                $item
            );
    }

    /**
     * @return bool
     */
    public function getIsSlider()
    {
        return $this->pluginEnable() && $this->_helper->isPriceSlider();
    }

    /**
     * @return bool|false|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSliderConfig()
    {
        /** @var Collection $productCollection */
        $productCollection = $this->getProductCollection();
        $format = explode('%s', $this->getOutputFormat());
        if (2 != count($format)) {
            $format = [
                $this->getCurrencySymbol(),
                ''
            ];
        }
        $config = [
            "min" => $productCollection->getMinPrice(),
            "max" => $productCollection->getMaxPrice(),
            "step" => 1,
            "formatMoney" => [
                "prefix" => $format[0] ?: '',
                "suffix" => $format[1] ?: '',
            ],
        ];

        return $this->json->serialize($config);
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencySymbol()
    {
        if (empty($this->currencySymbol)) {
            $this->currencySymbol = $this->getCurrentCurrency()->getCurrencySymbol();
        }

        return $this->currencySymbol;
    }

    /**
     * @return Currency
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrentCurrency()
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        /** @var Currency $currentCurrency */
        $currentCurrency = $store->getCurrentCurrency();
        return $currentCurrency;
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOutputFormat()
    {
        return $this->getCurrentCurrency()->getOutputFormat();
    }

    /**
     * @return array
     */
    protected function getCurrentValue()
    {
        return $this->attributeValues;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function _getItemsData()
    {
        if (!$this->_helper->isEnabled()) {
            return parent::_getItemsData();
        }

        if ($this->hasCurrentValue() && !$this->shouldVisible()) {
            return [];
        }

        $facets = $this->getFacetedData();

        $data = [];
        if (count($facets) > 1) { // two range minimum
            $lastFacet = array_key_last($facets);
            foreach ($facets as $key => $aggregation) {
                $count = $aggregation['count'];
                if (strpos($key, '_') === false) {
                    continue;
                }
                $isLast = $lastFacet === $key;
                $data[] = $this->prepareData($key, $count, $isLast);
            }
        }

        return $data;
    }

    /**
     * @return bool
     */
    protected function hasCurrentValue()
    {
        return !empty($this->attributeValues);
    }

    /**
     * @return bool
     */
    protected function shouldVisible()
    {
        return true;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @noinspection PhpDeprecationInspection
     */
    protected function getFacetedData()
    {
        $key = 'facet_price_' . $this->getRequestVar();
        if ($this->coreRegistry->registry($key) === null) {
            $this->coreRegistry->register($key, $this->generateFacetedData());
        }

        return $this->coreRegistry->registry($key);
    }

    /**
     * @return array
     * @throws LocalizedException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function generateFacetedData()
    {
        $productCollection = $this->getProductCollection();
        $attribute = $this->getAttributeModel();
        $attributeCode = $attribute->getAttributeCode();
        try {
            $facetedData = $productCollection->getFacetedData(
                $attributeCode,
                $this->getAlteredQueryResponse()
            );
        } catch (StateException $e) {
            if (!$this->messageManager->hasMessages()) {
                $this->messageManager->addErrorMessage(
                    __('Make sure that "%1" attribute can be used in layered navigation', $attributeCode)
                );
            }
            $facetedData = [];
        }

        return $facetedData;
    }

    /**
     * @return QueryResponse|ResponseInterface|null
     */
    protected function getAlteredQueryResponse()
    {
        $alteredQueryResponse = null;
        if ($this->hasCurrentValue() &&
            $this->scopeConfig->getValue(AlgorithmFactory::XML_PATH_RANGE_CALCULATION) !=
            AlgorithmFactory::RANGE_CALCULATION_IMPROVED) {
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
        $requestBuilder->removePlaceholder($attributeCode . '.from');
        $requestBuilder->removePlaceholder($attributeCode . '.to');
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
     * @param string $key
     * @param int $count
     * @return array
     */
    private function prepareData($key, $count, $isLast = false)
    {
        [$from, $to] = explode('_', $key);
        $label = $this->_renderRangeLabel(
            empty($from) ? 0 : $from * $this->getCurrencyRate(), // @todo may need to be removed * $this->getCurrencyRate()
            $to * $this->getCurrencyRate(), // @todo may need to be removed * $this->getCurrencyRate()
            $isLast
        );
        $value = $from . '-' . $to . $this->dataProvider->getAdditionalRequestData();

        $data = [
            'label' => $label,
            'value' => $value,
            'count' => $count,
            'from' => $from,
            'to' => $to,
        ];

        return $data;
    }
}