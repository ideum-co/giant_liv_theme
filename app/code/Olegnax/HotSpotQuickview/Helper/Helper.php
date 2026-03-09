<?php
/**
 * @author      Olegnax
 * @package     Olegnax_HotSpotQuickview
 * @copyright   Copyright (c) 2023 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\HotSpotQuickview\Helper;

use Magento\Framework\App\Helper\Context;
use Olegnax\Core\Helper\Helper as CoreHelperHelper;

class Helper extends CoreHelperHelper
{

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }
}
