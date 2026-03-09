<?php /**/
namespace Olegnax\Athlete2\Model\Config\Settings\Product;
use Magento\Framework\Option\ArrayInterface;

class ImageBehavior implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Default')],
            ['value' => 'center',  'label' => __('Align center in block')],
            ['value' => 'stretch',     'label' => __('Stretch to the block size')]
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
