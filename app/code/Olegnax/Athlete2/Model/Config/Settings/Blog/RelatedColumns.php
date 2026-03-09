<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Blog;

use Magento\Framework\Option\ArrayInterface;

class RelatedColumns implements ArrayInterface
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
            '2' => __('2'),
            '3' => __('3'),
			'4' => __('4'),
        ];
    }
}
