<?php
namespace Giant\Checkout\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class SimilarProducts extends AbstractProduct
{
    protected $productCollectionFactory;
    protected $productVisibility;
    protected $productStatus;
    protected $categoryRepository;

    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $productVisibility,
        Status $productStatus,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productVisibility = $productVisibility;
        $this->productStatus = $productStatus;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
    }

    public function getSimilarProducts()
    {
        $product = $this->_coreRegistry->registry('current_product');
        if (!$product) {
            return [];
        }

        $categoryIds = $product->getCategoryIds();
        $bestCategoryId = null;

        if (!empty($categoryIds)) {
            $maxLevel = 0;
            foreach ($categoryIds as $catId) {
                try {
                    $cat = $this->categoryRepository->get($catId);
                    if ($cat->getLevel() > $maxLevel) {
                        $maxLevel = $cat->getLevel();
                        $bestCategoryId = $catId;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if (!$bestCategoryId) {
            $bestCategoryId = 2;
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'small_image', 'thumbnail', 'image', 'url_key']);
        $collection->addCategoriesFilter(['in' => [$bestCategoryId]]);
        $collection->addAttributeToFilter('entity_id', ['neq' => $product->getId()]);
        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        $collection->addFinalPrice();
        $collection->setPageSize(6);
        $collection->getSelect()->orderRand();

        return $collection;
    }

    public function getProductSubcategory($product)
    {
        $categoryIds = $product->getCategoryIds();
        if (empty($categoryIds)) {
            return '';
        }

        $bestCat = null;
        $maxLevel = 0;
        foreach ($categoryIds as $catId) {
            try {
                $cat = $this->categoryRepository->get($catId);
                if ($cat->getLevel() > $maxLevel && $cat->getId() != 2) {
                    $maxLevel = $cat->getLevel();
                    $bestCat = $cat;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $bestCat ? $bestCat->getName() : '';
    }
}
