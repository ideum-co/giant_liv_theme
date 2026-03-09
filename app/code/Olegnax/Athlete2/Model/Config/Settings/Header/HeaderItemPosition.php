<?php /**/
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class HeaderItemPosition implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '',     'label' => __('Don\'t Show')],
            ['value' => 'topline',  'label' => __('in Top Line/My Account Drop')],
            ['value' => 'main',     'label' => __('in Main Header')],
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
