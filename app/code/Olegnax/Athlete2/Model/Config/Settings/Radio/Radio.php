<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Radio;

use Magento\Framework\Option\ArrayInterface;

class Radio implements ArrayInterface
{
   public function toOptionArray()
	{
    	return [['value' => 'left', 'label' => __('Left')], ['value' => 'right', 'label' => __('Right')]];
	}
}
