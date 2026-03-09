<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Carousel;

use Magento\Framework\Option\ArrayInterface;

class Align implements ArrayInterface
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
            'left' => __('Left'),
            'center' => __('Center'),
        ];
    }
}
