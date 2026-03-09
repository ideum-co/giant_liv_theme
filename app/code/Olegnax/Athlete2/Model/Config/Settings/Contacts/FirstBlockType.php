<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Contacts;

use Magento\Framework\Option\ArrayInterface;

class FirstBlockType implements ArrayInterface
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
            'content' => __('Content'),
            'image' => __('Image'),
            'google_map' => __('Google Map'),
        ];
    }
}
