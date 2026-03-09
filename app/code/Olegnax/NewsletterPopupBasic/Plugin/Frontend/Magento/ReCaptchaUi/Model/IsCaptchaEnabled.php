<?php
/*
 * @author      Olegnax
 * @package     Olegnax_NewsletterPopupBasic
 * @copyright   Copyright (c) 2021 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\NewsletterPopupBasic\Plugin\Frontend\Magento\ReCaptchaUi\Model;

use Magento\Framework\App\Request\Http;
use Olegnax\NewsletterPopupBasic\Helper\Helper;

class IsCaptchaEnabled
{
    const INPUT_ATTR = 'ox_captcha';
    const INPUT_VALUE = 'ox_captcha';
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var Http
     */
    protected $_request;

    public function __construct(
        Http $request,
        Helper $helper
    ) {
        $this->_request = $request;
        $this->_helper = $helper;
    }

    public function afterIsCaptchaEnabledFor(
        \Magento\ReCaptchaUi\Model\IsCaptchaEnabled $subject,
        $result,
        $key = ''
    ) {
        if ( 'newsletter' == $key && $this->_helper->isEnabled() && static::INPUT_VALUE === $this->_request->getParam(static::INPUT_ATTR, '') ) {

            $result = false;
        }
        return $result;
    }
}