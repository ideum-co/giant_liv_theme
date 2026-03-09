<?php /**/
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class BannerType implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'textfield', 'label' => __('Custom HTML')],
            ['value' => 'custom_block', 'label' => __('Static Block')]
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
