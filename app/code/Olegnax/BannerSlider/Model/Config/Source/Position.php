<?php

namespace Olegnax\BannerSlider\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Position implements ArrayInterface {

	public function toOptionArray() {
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
			],
			[
				'value' => 'center',
				'label' => __('Center')
			],
		];
	}

}
