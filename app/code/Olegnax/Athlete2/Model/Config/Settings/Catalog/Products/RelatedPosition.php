<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Catalog\Products;

use Magento\Framework\Option\ArrayInterface;

class RelatedPosition implements ArrayInterface
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
            '' => __('Default, In Content'),
            'oxbottom' => __('Before Page Bottom, After Content'),
			'bottom' => __('Page Bottom'),
			'gallery' => __('Below Gallery')
        ];
    }
}
