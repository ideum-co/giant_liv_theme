<?php
namespace Giant\SheetSync\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class SyncDashboard extends Template
{
    public function getTestConnectionUrl()
    {
        return $this->getUrl('sheetsync/sync/testConnection');
    }

    public function getRunSyncUrl()
    {
        return $this->getUrl('sheetsync/sync/run');
    }

    public function getSpreadsheetUrl()
    {
        return 'https://docs.google.com/spreadsheets/d/1b8cS_9ERLh_aJ7jdw-VD7IWIyrf_Ia-gizgIbvE-mzg/edit';
    }
}
