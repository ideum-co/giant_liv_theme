<?php

namespace Olegnax\Carousel\Model\Slide\Source;

use Magento\Framework\Option\ArrayInterface;

class ButtonStyle implements ArrayInterface
{

	public function toOptionArray() {
		return [
			[
				'value' => '',
				'label' => __('Theme')
			],
			[
				'value' => 'simple',
				'label' => __('Simple')
			],
			[
				'value' => 'naked',
				'label' => __('Naked')
			],
			[
				'value' => 'outline',
				'label' => __('OutLine')
			],
			[
				'value' => 'underline',
				'label' => __('Underlined')
			],
		];
	}

}
