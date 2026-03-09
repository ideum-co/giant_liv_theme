<?php /**/
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class HeaderItemPositionAccount implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'none',     'label' => __('Don\'t Show')],
            ['value' => 'topline',  'label' => __('in Top Line')],
            ['value' => 'main',     'label' => __('in Main Header')]
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
