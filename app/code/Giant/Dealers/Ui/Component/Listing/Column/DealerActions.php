<?php
namespace Giant\Dealers\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class DealerActions extends Column
{
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['dealer_id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl('dealers/dealer/edit', ['dealer_id' => $item['dealer_id']]),
                            'label' => __('Editar')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl('dealers/dealer/delete', ['dealer_id' => $item['dealer_id']]),
                            'label' => __('Eliminar'),
                            'confirm' => [
                                'title' => __('Eliminar Distribuidor'),
                                'message' => __('¿Está seguro que desea eliminar este distribuidor?')
                            ]
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
