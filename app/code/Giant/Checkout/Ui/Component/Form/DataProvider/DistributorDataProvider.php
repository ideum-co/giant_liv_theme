<?php
declare(strict_types=1);

namespace Giant\Checkout\Ui\Component\Form\DataProvider;

use Giant\Checkout\Model\ResourceModel\Distributor\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DistributorDataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $distributor) {
            $this->loadedData[$distributor->getId()] = $distributor->getData();
        }

        return $this->loadedData;
    }
}
