<?php

namespace Olegnax\Athlete2\Model;

use Exception;
use Magento\Framework\Notification\MessageInterface;
use Olegnax\Athlete2\Helper\Helper;

class Message implements MessageInterface
{
    const MESSAGE_IDENTITY = 'ox_athlete2';

    protected $_text;
    protected $_helper;

    public function __construct(Helper $helper)
    {
        $this->_helper = $helper;
        $license = $helper->get();
        if ($this->_helper->isEnabled()
            && !empty($license)
            && isset($license->data->the_key)
            && $license->data->the_key == $helper->getSystemDefaultValue('athlete2_license/general/code')
            && $license->data->status == "active"
        ) {
            try {
                if (!$this->_helper->validate()) {
                    throw new Exception('License validate failed');
                }
            } catch (Exception $e) {
                $this->_text = sprintf(__('Olegnax Athlete2: %s'), $e->getMessage());
            }
        }
    }

    public function getIdentity()
    {
        return static::MESSAGE_IDENTITY;
    }

    public function getSeverity()
    {
        return MessageInterface::SEVERITY_CRITICAL;
    }

    public function isDisplayed()
    {
        $text = $this->getText();
        return !empty($text);
    }

    public function getText()
    {
        return $this->_text;
    }

}