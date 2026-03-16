<?php
declare(strict_types=1);

namespace Giant\Checkout\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class DistributorActions extends Column
{
    const URL_PATH_EDIT   = 'giant_checkout/distributor/edit';
    const URL_PATH_DELETE = 'giant_checkout/distributor/delete';

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $name = $this->getData('name');
            $id   = $item['entity_id'];

            $item[$name]['edit'] = [
                'href'  => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['entity_id' => $id]),
                'label' => __('Editar'),
            ];

            $item[$name]['delete'] = [
                'href'    => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['entity_id' => $id]),
                'label'   => __('Eliminar'),
                'confirm' => [
                    'title'   => __('Eliminar distribuidor'),
                    'message' => __('¿Estás seguro de que deseas eliminar este distribuidor?'),
                ],
                'post'    => true,
            ];
        }

        return $dataSource;
    }
}
