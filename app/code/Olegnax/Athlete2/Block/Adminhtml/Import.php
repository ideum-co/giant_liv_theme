<?php

namespace Olegnax\Athlete2\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Import extends Template {

	protected $_filesystem;
	protected $_dir;

	public function __construct( Context $context,
							  Filesystem $filesystem, array $data = []
	) {
		$this->_filesystem = $filesystem;
		parent::__construct( $context, $data );
	}

	public function getWebsiteId() {
		$storeModel	 = $this->_objectManager->create( 'Magento\Store\Model\Store' );
		$store		 = $storeModel->load( $this->getRequest()->getParam( 'store' ) );
		return $store->getWebsiteId();
	}

	public function getAbsolutePath( $path ) {
		return $this->_filesystem->getDirectoryRead( DirectoryList::APP )->getAbsolutePath( $path );
	}

}
