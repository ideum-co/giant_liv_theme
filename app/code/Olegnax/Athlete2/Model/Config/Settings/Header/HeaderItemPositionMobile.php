<?php /**/
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;

class HeaderItemPositionMobile implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
			['value' => '',     'label' => __('Inherit, Same as Desktop')],
            ['value' => 'none',     'label' => __('Don\'t Show')],
            ['value' => 'main',     'label' => __('in Main Header')],
			['value' => 'slideout',  'label' => __('in Navigation Slideout')]
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
