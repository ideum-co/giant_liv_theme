<?php
/**
 * @author      Olegnax
 * @package     Olegnax_LayeredNavigation
 * @copyright   Copyright (c) 2019 Olegnax (http://olegnax.com/). All rights reserved.
 * @license     Proprietary License https://olegnax.com/license/
 */

namespace Olegnax\LayeredNavigation\Model\Layer\Filter;


use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Framework\Search\ResponseInterface;
use Magento\Search\Model\SearchEngine;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Olegnax\LayeredNavigation\Helper\Helper;
use Olegnax\LayeredNavigation\Model\Layer\Filter;
use Olegnax\LayeredNavigation\Model\Request\Builder;
use Olegnax\LayeredNavigation\Model\ResourceModel\Fulltext\Collection;
use Psr\Log\LoggerInterface;

class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category
{
    const FILTER_CODE = 'category';
    const ATTRIBUTE_CODE = 'category_ids';
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var array
     */
    protected $attributeValues;
    /**
     * @var mixed
     */
    protected $currentId;
    /**
     * @var bool
     */
    protected $isSearch;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var SearchEngine
     */
    protected $searchEngine;
    /**
     * @var Filter
     */
    protected $layerFilter;
    /**
     * @var array
     */
    protected $categorys = [];
    /**
     * @var CategoryModel
     */
    protected $category;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Layer\Filter\DataProvider\Category
     */
    private $dataProvider;
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var int
     */
    protected $maxDepth;

    /**
     * Category constructor.
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param Escaper $escaper
     * @param CategoryFactory $categoryDataProviderFactory
     * @param Helper $helper
     * @param SearchEngine $searchEngine
     * @param ManagerInterface $messageManager
     * @param Filter $layerFilter
     * @param CategoryModel $category
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        Escaper $escaper,
        CategoryFactory $categoryDataProviderFactory,
        Helper $helper,
        SearchEngine $searchEngine,
        ManagerInterface $messageManager,
        Filter $layerFilter,
        CategoryModel $category,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $categoryDataProviderFactory,
            $data
        );
        $this->storeManager = $storeManager;
        $this->_helper = $helper;
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->searchEngine = $searchEngine;
        $this->messageManager = $messageManager;
        $this->escaper = $escaper;
        $this->layerFilter = $layerFilter;
        $this->category = $category;
    }

    public function getMaxDepth()
    {
        if (null == $this->maxDepth) {
            $this->maxDepth = abs((int)$this->_helper->getModuleConfig('category/depth'));
        }
        return $this->maxDepth;
    }

    /**
     * @param RequestInterface $request
     * @return $this|Category
     * @noinspection PhpDeprecationInspection
     */
    public function apply(RequestInterface $request)
    {
        if (!$this->pluginEnable()) {
            return parent::apply($request);
        }
        $this->currentId = $request->getParam('id');
        $this->isSearch = (bool)$request->getParam('q');
        $categoryId = $request->getParam($this->_requestVar) ?: $this->currentId;
        if (empty($categoryId)) {
            return $this;
        }

        $categoryIds = explode(',', $categoryId);
        $categoryIds = array_unique($categoryIds);
        $this->setCurrentValue($categoryIds);

        /** @var Collection $productCollection */
        $productCollection = $this->getProductCollection();
        if ($this->isMultiselect() && count($categoryIds) > 1) {
            $productCollection->addIndexCategoriesFilter(['in' => $categoryIds]);
            $category = $this->getLayer()->getCurrentCategory();
            $child = $category->getCollection()
                ->addFieldToFilter($category->getIdFieldName(), ['in' => $categoryIds])
                ->addAttributeToSelect('name');
            if ($this->shouldAddState()) {
                $this->addState($categoryIds, $child);
            }
        } else {
            $this->dataProvider->setCategoryId($categoryId);
            $productCollection->addCategoryFilter($this->dataProvider->getCategory());
            if ($this->shouldAddState()) {
                $this->addState();
            }
        }

        if (!$this->shouldVisible()) {
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
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection()
    {
        return $this->getLayer()->getProductCollection();
    }

    /**
     * @return bool
     */
    protected function isMultiselect()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function shouldAddState()
    {
        return true;
    }

    /**
     * @param array $values
     * @param null $child
     */
    protected function addState($values = [], $child = null)
    {
        if ($child) {
            $labels = [];
            foreach ((array)$values as $value) {
                if ($currentCategory = $child->getItemById($value)) {
                    $labels[$currentCategory->getId()] = $currentCategory->getName();
                }
            }
            foreach ($labels as $id => $categoryName) {
                $state = $this->_createItem($categoryName, $id);
                $this->getLayer()->getState()->addFilter($state);
            }
        } else {
            $category = $this->dataProvider->getCategory();
            if ($this->getCurrentCategoryId() != $category->getId() && $this->dataProvider->isValid()) {
                $state = $this->_createItem($category->getName(), $category->getId());
                $this->getLayer()->getState()->addFilter($state);
            }
        }

    }

    public function getCurrentCategoryId()
    {
        return $this->currentId ?: $this->getStore()->getRootCategoryId(); //$this->getRootCategory()->getId();
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore()
    {
        return $this->storeManager->getStore();
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
     */
    public function _getItemsData()
    {
        if (!$this->pluginEnable()) {
            return parent::_getItemsData();
        }

        $optionsFacetedData = $this->getOptionsFacetedData();
        $this->dataProvider->setCategoryId($this->getCurrentCategoryId());
        $categories = $this->getCategoryTree($this->dataProvider->getCategory());
        foreach ($categories as $key => $category) {
            if (isset($optionsFacetedData[$key])) {
                $categories[$key]['count'] = $optionsFacetedData[$key]['count'];
                $path = explode('/', $category['path']);
                foreach ($path as $id) {
                    if (array_key_exists($id, $categories)) {
                        $categories[$id]['use'] = true;
                    }
                }
            }
        }
        $categories = array_filter($categories, [$this, 'filterCategory']);

        return $categories;
    }

    /**
     * Initialize filter items
     *
     * @return  \Magento\Catalog\Model\Layer\Filter\AbstractFilter
     */
    protected function _initItems()
    {
        $data = $this->_getItemsData();
        $items = [];
        foreach ($data as $itemData) {
            $items[] = $this->_createItem(
                $itemData['label'],
                $itemData['value'],
                $itemData['count']
            )
                ->setData('item_level', isset($itemData['item_level']) ? $itemData['item_level'] : 0)
                ->setData('path', isset($itemData['path']) ? $itemData['path'] : '');
        }
        $this->_items = $items;
        return $this;
    }

    /**
     * @return array
     */
    protected function getOptionsFacetedData()
    {
        return $this->generateOptionsFacetedData();
    }

    /**
     * @return array
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function generateOptionsFacetedData()
    {
        $productCollection = $this->getProductCollection();
        $attributeCode = static::FILTER_CODE;
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
        $categoryIds = $this->getCurrentValue();
        $categoryId = !empty($categoryIds) ? $categoryIds[0] : null;
        if ($this->hasCurrentValue() && $categoryId != $this->getCurrentCategoryId()) {
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
     * @return array
     */
    protected function getCurrentValue()
    {
        return $this->attributeValues;
    }

    /**
     * @return bool
     */
    protected function hasCurrentValue()
    {
        return !empty($this->attributeValues);
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
        $attributeCode = static::ATTRIBUTE_CODE;
        $requestBuilder->removePlaceholder($attributeCode);
        $requestBuilder->bind($attributeCode, $this->getCurrentCategoryId());
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
     * @param CategoryModel $category
     * @param int $level
     * @return array
     */
    private function getCategoryTree($category, $level = 0)
    {
        $result = [];
        if ($category->getIsActive()) {
            $categories = $category->getChildrenCategories();
            foreach ($categories as $category) {
                if ($category->getIsActive()) {
                    $id = $category->getId();
                    $result[$id] = [
                        'label' => $this->escaper->escapeHtml($category->getName()),
                        'value' => $id,
                        'item_level' => $level,
                        'path' => $category->getPath(),
                        'count' => 0,
                    ];
                    if ($this->getMaxDepth() > $level) {
                        $subItem = $this->getCategoryTree($category, $level + 1);
                        foreach ($subItem as $key => $item) {
                            $result[$key] = $item;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param array $item
     * @return bool
     */
    public function filterCategory($item)
    {
        return is_array($item) && array_key_exists('use', $item);
    }

    /**
     * @param $filterItem
     * @return bool
     */
    public function getIsDisableAjax($filterItem)
    {
        $categoryItem = $this->getCategoryItem($filterItem);
        return $categoryItem->getData('ox_nav_disable_ajax') || !$categoryItem->getIsAnchor();
    }

    /**
     * @param $filterItem
     * @return mixed
     */
    private function getCategoryItem($filterItem)
    {
        $value = $filterItem->getValue();
        if (!array_key_exists($value, $this->categorys)) {
            $category = clone $this->category;
            $this->categorys[$value] = $category->load($value);
        }

        return $this->categorys[$value];
    }

    /** @noinspection PhpDeprecationInspection */

    /**
     * @param $filterItem
     * @return mixed
     */
    public function getUrlItem($filterItem)
    {
        return $this->getCategoryItem($filterItem)->getUrl();
    }
}