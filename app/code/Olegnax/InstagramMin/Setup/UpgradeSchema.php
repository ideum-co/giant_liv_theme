<?php 
namespace Olegnax\InstagramMin\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Notification\NotifierInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /** @var WriterInterface */
    private $configWriter;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var NotifierInterface */
    private $notifierPool;

    /**
     * UpgradeSchema constructor.
     *
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param NotifierInterface $notifier
     */
    public function __construct(WriterInterface $configWriter, ScopeConfigInterface $scopeConfig, NotifierInterface $notifierPool)
    {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->notifierPool = $notifierPool;
    }
    /**
     * Perform module upgrade actions.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.1.0.0', '<')) {
            $this->resetToken($setup);            
        }

        $installer->endSetup();
    }

    /**
     * Clear unsafe token
     *
     * @param SchemaSetupInterface $setup
     */
    private function resetToken(SchemaSetupInterface $setup)
    {
        $flag = false;
        foreach (['access_token', 'user_id', 'expire'] as $key) {
            $path = "olegnax_instagram/oauth/" . $key;
            $value = $this->scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
            if ($value !== null) {
                $this->configWriter->save($path, '', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

            }
            if ($key === 'access_token' && $value !== null) {
                $flag = true;
            }
        }
        if($flag){
            $this->addNotificationMessage();
        }
    }
    /**
     * Add a notification message.
     */
    private function addNotificationMessage()
    {
        // Replace with your notification message content
        $message = 'Instagram token has been removed! Please regenerate it in Olegnax / Instagram / Configuration';
        // Add major severity message
        $this->notifierPool->addMajor(
            'Olegnax Instagram',
            $message,
            'https://athlete2.com/documentation/changelog.html'
        );
    }
}