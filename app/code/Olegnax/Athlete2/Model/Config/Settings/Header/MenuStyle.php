<?php
namespace Olegnax\Athlete2\Model\Config\Settings\Header;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\View\Asset\Repository;

class MenuStyle implements ArrayInterface
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

    public function toArray() {
        return [
            'menu-style-1' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/header-menu-v1.png' ),
			'menu-style-2' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/header-menu-v2.png' ),
			'menu-style-3' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/header-menu-v3.png' ),
			'menu-style-4' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/header-menu-v4.png' ),
			'menu-style-5' => $this->_assetRepo->getUrl( 'Olegnax_Athlete2::images/header-menu-v5.png' ),
        ];
    }
}
