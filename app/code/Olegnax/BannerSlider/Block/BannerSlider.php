<?php

/**
 * Olegnax BannerSlider
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Olegnax.com license that is
 * available through the world-wide-web at this URL:
 * https://www.olegnax.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Olegnax
 * @package     Olegnax_BannerSlider
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\BannerSlider\Block;

use Magento\Customer\Model\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;
use Olegnax\BannerSlider\Model\ResourceModel\Group\CollectionFactory;

class BannerSlider extends Template implements BlockInterface {

	protected $_slidesSollection;
	protected $_groupSollection;

	/**
	 * @var \Magento\Framework\App\Http\Context
	 */
	protected $httpContext;

	/**
	 * Json Serializer Instance
	 *
	 * @var Json
	 */
	private $json;

	public function __construct(
	\Magento\Framework\View\Element\Template\Context $context,
 \Olegnax\BannerSlider\Model\ResourceModel\Slides\CollectionFactory $slidesCollectionFactory,
 CollectionFactory $groupCollectionFactory,
 \Magento\Framework\App\Http\Context $httpContext, array $data = [],
 Json $json = null
	) {
		$this->_slidesSollection = $slidesCollectionFactory->create();
		$this->_groupSollection	 = $groupCollectionFactory->create();

		$this->httpContext	 = $httpContext;
		$this->json			 = $json ?: ObjectManager::getInstance()->get( Json::class );
		parent::__construct( $context, $data );
	}

	protected function _construct() {
		$this->addData( [
			'cache_lifetime' => 86400,
		] );
		if ( !$this->hasData( 'template' ) && !$this->getTemplate() ) {
			$this->setTemplate( 'Olegnax_BannerSlider::bannerslider.phtml' );
		}
		parent::_construct();
	}

	public function getCacheKeyInfo( $newval = [] ) {
		return array_merge( [
			'OLEGNAX_BANNERSLIDER_WIDGET',
			$this->_storeManager->getStore()->getId(),
			$this->_design->getDesignTheme()->getId(),
			$this->httpContext->getValue( Context::CONTEXT_GROUP ),
			$this->json->serialize( $this->getRequest()->getParams() ),
			$this->json->serialize( $this->getData() ),
		], parent::getCacheKeyInfo(), $newval );
	}

	public function getConfig( $path, $storeCode = null ) {
        $value = $this->_scopeConfig->getValue( $path, ScopeInterface::SCOPE_STORE, $storeCode );
        if (is_null($value)) {
            $value = '';
        }
        return $value;
	}

	public function getSlides() {
		$group = $this->getGroup();
		if ( !empty( $group ) ) {
			return $this->_slidesSollection
			->addFieldToSelect( '*' )
			->addStoreFilter( $this->_storeManager->getStore() )
			->addFieldToFilter( 'slide_group', $group[ 'group_id' ] )
			->addFieldToFilter( 'status', 1 )
			->setOrder( 'sort_order', 'asc' );
		}

		return [];
	}

	public function getGroup() {
		$group = $this->getData( 'current_group' );
		if ( empty( $group ) ) {
			$groups = $this->_groupSollection
			->addFieldToSelect( '*' )
			->addFieldToFilter( 'identifier', $this->getSlideGroup() );
			if ( $groups->getSize() ) {
				foreach ( $groups as $group ) {
					$this->setData( 'current_group', $group );
					break;
				}
			}
		}

		return $group;
	}

	public function getSliderId() {
		return 'ox_' . $this->getNameInLayout() . substr( md5( microtime() ), -5 );;
	}

	public function prepareStyle( array $style, string $separatorValue = ': ', string $separatorAttribute = ';' ) {
		$style = array_filter( $style );
		if ( empty( $style ) ) {
			return '';
		}
		foreach ( $style as $key => &$value ) {
			$value = $key . $separatorValue . $value;
		}
		$style = implode( $separatorAttribute, $style );

		return $style;
	}

	public function getResponsive( $to_string = true ) {
		$width		 = $this->getGroup()->getSlideWidth();
		$responsive	 = [
			0 => [
				'items' => 1,
			],
		];
		$j			 = 1;
		for ( $i = $width + 1; $i < 3000; $i += $width ) {
			$j++;
			$responsive[ $i ] = [
				'items' => $j,
			];
		}

		if ( $to_string ) {
			return json_encode( $responsive );
		}

		return $responsive;
	}

	public function getAutoScroll() {
		$auto_scroll = $this->getData( 'auto_scroll' );
		if ( empty( $auto_scroll ) ) {
			$auto_scroll = 0;
		}

		return $auto_scroll;
	}

}
