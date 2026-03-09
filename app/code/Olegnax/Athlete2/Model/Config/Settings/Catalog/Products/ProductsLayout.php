<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Catalog\Products;

use Magento\Framework\Option\ArrayInterface;

class ProductsLayout implements ArrayInterface
{
	public function toOptionArray() {
		$optionArray = [ ];
		$array		 = $this->toArray();
		foreach ( $array as $key => $value ) {
			$optionArray[] = [ 'value' => $key, 'label' => $value ];
		}

		return $optionArray;
	}

    public function toArray()
    {
        return [
            '1' => __('All Actions Above Image - Centered'),
			'3' => __('Add to cart Bottom, Secondary Above Image - Centered'),
            '2' => __('Quickview Above Image - Bottom Left, Secondary Bottom'),
            '4' => __('Actions Bottom, Always Visible'),
            '5' => __('Add to cart Bottom, Secondary Above Image - Top Right'),
        ];
    }
}
