<?php
namespace Olegnax\Core\Model\ResourceModel\Inbox\Collection;

use Magento\Framework\ObjectManagerInterface;

class ExpiredFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return Expired
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create(Expired::class, $data);
    }
}
