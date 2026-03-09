<?php

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class Margins implements ArrayInterface
{

	public function toOptionArray() {
		return [
			[
				'value' => 'normal',
				'label' => __('Normal')
			],
			[
				'value' => 'big',
				'label' => __('Big')
			],
		];
	}

}
