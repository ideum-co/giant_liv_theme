<?php

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class MobileAlign implements ArrayInterface
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
		];
	}

}
