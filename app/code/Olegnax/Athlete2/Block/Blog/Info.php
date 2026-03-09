<?php

namespace Olegnax\Athlete2\Block\Blog;

use Magefan\Blog\Model\Config\Source\CommetType;
use Magefan\Blog\Model\Post;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class Info extends Template {

	protected $_coreRegistry;

	public function __construct(
		Context $context, Registry $coreRegistry, array $data = []
	) {
		parent::__construct( $context, $data );
		$this->_coreRegistry = $coreRegistry;
	}

	/**
	 * Retrieve post instance
	 *
	 * @return Post
	 */
	public function getPost() {
		if ( !$this->hasData( 'post' ) ) {
			$this->setData(
			'post', $this->_coreRegistry->registry( 'current_blog_post' )
			);
		}
		return $this->getData( 'post' );
	}

	/**
	 * Block template file
	 * @var string
	 */
	protected $_template = 'Magefan_Blog::post/info.phtml';

	/**
	 * DEPRECATED METHOD!!!!
	 * Retrieve formated posted date
	 * @var string
	 * @return string
	 */
	public function getPostedOn( $format = 'Y-m-d H:i:s' ) {
		return $this->getPost()->getPublishDate( $format );
	}

	/**
	 * Retrieve 1 if display author information is enabled
	 * @return int
	 */
	public function authorEnabled() {
		return (int) $this->_scopeConfig->getValue(
		'mfblog/author/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}

	/**
	 * Retrieve 1 if author page is enabled
	 * @return int
	 */
	public function authorPageEnabled() {
		return (int) $this->_scopeConfig->getValue(
		'mfblog/author/page_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
	}

	/**
	 * Retrieve true if magefan comments are enabled
	 * @return bool
	 */
	public function magefanCommentsEnabled() {
		return $this->_scopeConfig->getValue(
		'mfblog/post_view/comments/type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		) == CommetType::MAGEFAN;
	}

}
