<?php

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class Wrappers implements ArrayInterface
{

	public function toOptionArray() {
		return [
			[
				'value' => 'container',
				'label' => __('Container')
			],
			[
				'value' => 'no-container',
				'label' => __('No Container')
			],
			[
				'value' => 'raw',
				'label' => __('Raw')
			],
		];
	}

}
