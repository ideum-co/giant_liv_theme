<?php

namespace Nwdthemes\Revslider\Observer;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Nwdthemes\Revslider\Helper\Data;
use Nwdthemes\Revslider\Model\OptionFactory;
use Nwdthemes\Revslider\Helper\Framework;

class LayoutLoadBefore implements ObserverInterface
{
    protected $_frameworkHelper;
    protected $_optionFactory;
    protected $_pageRepository;
    protected $_request;
    protected $_status;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        Context $context,
        OptionFactory $optionFactory,
        Framework $frameworkHelper
    ) {
        $this->_optionFactory = $optionFactory;
        $this->_pageRepository = $pageRepository;
        $this->_request = $context->getRequest();
        $this->_status = $context->getScopeConfig()->getValue(
            'nwdthemes_revslider/revslider_configuration/status',
            ScopeInterface::SCOPE_STORE
        );
        $this->_frameworkHelper = $frameworkHelper;
    }

    public function execute(Observer $observer)
    {
        if ($this->_status) {

            $layoutUpdate = $observer->getData('layout')->getUpdate();

            $option = $this->_optionFactory
                ->create()
                ->getCollection()
                ->addFieldToFilter('handle', 'revslider-global-settings')
                ->setPageSize(1)
                ->getFirstItem()
                ->getData('option');
            $settings = $this->_frameworkHelper->json_decode($option, true);

            $includeSlider = ! isset($settings['allinclude']) || $settings['allinclude'] == 'true';
            if ( ! $includeSlider && isset($settings['includeids'])) {

                $pageHandles = $layoutUpdate->getHandles();
                if ($pageId = $this->_request->getParam('page_id', $this->_request->getParam('id', false))) {
                    try {
                        if ($page = $this->_pageRepository->getById($pageId)) {
                            $pageHandles[] = $page->getIdentifier();
                        }
                    } catch (\Exception $e) {}
                }
                if ($this->_request->getFullActionName() == 'cms_index_index') {
                    $pageHandles[] = 'homepage';
                }

                $arrHandles = explode(',', $settings['includeids']);
                foreach ($arrHandles as $handle) {
                    $handle = trim($handle);
                    if (
                        in_array($handle, $pageHandles)
                        || in_array(str_replace('-', '_', $handle), $pageHandles)
                        || in_array(str_replace('_', '-', $handle), $pageHandles)
                    ) {
                        $includeSlider = true;
                        continue;
                    }
                }

            }

            if ($includeSlider) {
                $layoutUpdate->addHandle('nwdthemes_revslider_default');
            }
        }
    }

}