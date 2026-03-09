<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class MenuAlignSticky implements ArrayInterface
{
	public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Same as Header (inherit)')],
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
