<?php

namespace Olegnax\ProductLabel\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class Position implements ArrayInterface {

	public function toOptionArray()
    {
        return [
			[
				'value' => 'top-left',
				'label' => __('Top Left')
			],
			[
				'value' => 'top-right',
				'label' => __('Top Right')
			],
			[
				'value' => 'bottom-left',
				'label' => __('Bottom Left')
			],
			[
				'value' => 'bottom-right',
				'label' => __('Bottom Right')
			]
        ];
    }
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }

}
