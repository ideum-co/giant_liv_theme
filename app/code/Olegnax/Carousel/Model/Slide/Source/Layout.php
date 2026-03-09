<?php

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class Layout implements ArrayInterface
{

	public function toOptionArray() {
		return [
			[
				'value' => 'left',
				'label' => __('Left')
			],
			[
				'value' => 'right',
				'label' => __('Right')
			],
			[
				'value' => 'center',
				'label' => __('Center')
			],
			[
				'value' => '2-col',
				'label' => __('2 Columns')
			],
		];
	}

}
