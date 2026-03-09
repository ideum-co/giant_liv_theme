<?php
/**
 * @author      Olegnax
 * @package     Olegnax_InfiniteScroll
 * @copyright   Copyright (c) 2019 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Olegnax\InfiniteScroll\Model\Config\Source;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Spacer extends Field {
    public function __construct(
    Context $context, array $data = []
    ) {
        parent::__construct($context, $data);
    }

	protected function _decorateRowHtml( AbstractElement $element, $html)
	{
		return '<tr id="row_' . $element->getHtmlId() . '"><td></td><td colspan="2"><hr class="ox-settings-spacer"></td></tr>';
	}

}
