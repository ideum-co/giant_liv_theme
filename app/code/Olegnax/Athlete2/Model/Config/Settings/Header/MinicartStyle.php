<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class MinicartStyle implements ArrayInterface
{
	public function toOptionArray()
    {
        return [
            ['value' => 'classic',     'label' => __('Athlete Classic')],
            ['value' => 'modern',  'label' => __('Modern')],
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
