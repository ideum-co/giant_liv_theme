<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Header;

use Magento\Framework\Option\ArrayInterface;

class MenuPosition implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('In Header')],
            ['value' => '2', 'label' => __('Below Header')],
        ];
    }

    public function toArray()
    {
        return [
            '1' => __('In Header'),
            '2' => __('Below Header'),
        ];
    }
}
