<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class MenuAlign implements ArrayInterface
{
	public function toOptionArray()
    {
        return [
            ['value' => 'left',     'label' => __('Left')],
            ['value' => 'center',  'label' => __('Center')],
            ['value' => 'right',     'label' => __('Right')]
        ];
    }
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}
