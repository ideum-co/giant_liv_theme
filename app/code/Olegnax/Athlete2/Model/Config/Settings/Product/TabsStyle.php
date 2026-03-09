<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Product;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\Asset\Repository;

class TabsStyle implements ArrayInterface
{
    protected $_assetRepo;

    public function __construct(
        Repository $assetRepo
    ) {
        $this->_assetRepo = $assetRepo;
    }
    public function toOptionArray() {
        $optionArray = [ ];
        $array		 = $this->toArray();
        foreach ( $array as $key => $value ) {
            $optionArray[] = [ 'value' => $key, 'label' => $value ];
        }

        return $optionArray;
    }

    public function toArray()
    {
        return [
            '' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/tabs-style-01.png' ),
            'minimal' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/tabs-style-02.png' ),
        ];
    }
}
