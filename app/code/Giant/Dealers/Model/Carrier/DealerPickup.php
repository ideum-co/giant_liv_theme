<?php
namespace Giant\Dealers\Model\Carrier;

use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Psr\Log\LoggerInterface;
use Giant\Dealers\Model\ResourceModel\Dealer\CollectionFactory;

class DealerPickup extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'dealerpickup';
    protected $_isFixed = true;
    protected $rateResultFactory;
    protected $rateMethodFactory;
    protected $collectionFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }

        $hasBike = false;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                $product = $item->getProduct();
                if ($product) {
                    $categoryIds = $product->getCategoryIds();
                    if ($this->isBicycleCategory($categoryIds)) {
                        $hasBike = true;
                        break;
                    }
                }
            }
        }

        if (!$hasBike) {
            return false;
        }

        $result = $this->rateResultFactory->create();
        $dealers = $this->collectionFactory->create();
        $dealers->addFieldToFilter('is_active', 1);
        $dealers->setOrder('sort_order', 'ASC');

        foreach ($dealers as $dealer) {
            $method = $this->rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod('dealer_' . $dealer->getId());
            $method->setMethodTitle($dealer->getData('name') . ' - ' . $dealer->getData('city'));
            $method->setPrice(0);
            $method->setCost(0);
            $result->append($method);
        }

        return $result;
    }

    protected function isBicycleCategory($categoryIds)
    {
        $bicycleCategoryId = $this->getConfigData('bicycle_category_id');
        if (!$bicycleCategoryId) {
            $bicycleCategoryId = 4;
        }

        if (in_array($bicycleCategoryId, $categoryIds)) {
            return true;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $categoryResource = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Category::class);

        foreach ($categoryIds as $catId) {
            $category = $objectManager->create(\Magento\Catalog\Model\Category::class);
            $categoryResource->load($category, $catId);
            $path = $category->getPath();
            if ($path && strpos($path, '/' . $bicycleCategoryId . '/') !== false) {
                return true;
            }
            if ($path && substr($path, -strlen('/' . $bicycleCategoryId)) === '/' . $bicycleCategoryId) {
                return true;
            }
        }

        return false;
    }

    public function getAllowedMethods()
    {
        $methods = [];
        $dealers = $this->collectionFactory->create();
        $dealers->addFieldToFilter('is_active', 1);
        foreach ($dealers as $dealer) {
            $methods['dealer_' . $dealer->getId()] = $dealer->getData('name');
        }
        return $methods;
    }
}
