<?php
declare(strict_types=1);

namespace Giant\Checkout\Controller\Index;

use Giant\Checkout\Api\DistributorRepositoryInterface;
use Giant\Checkout\Helper\Cart as CartHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * AJAX endpoint: GET /giant/index/distributors
 * Returns active distributors for the current store as JSON.
 * Returns an empty array (403 equivalent) if cart has no bicycle.
 */
class Distributors implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory                    $jsonFactory,
        private readonly DistributorRepositoryInterface $distributorRepository,
        private readonly CartHelper                     $cartHelper,
        private readonly RequestInterface               $request
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->cartHelper->hasBicycleInCart()) {
            return $result->setData(['distributors' => [], 'requires_bicycle' => true]);
        }

        $storeId    = $this->cartHelper->getCurrentStoreId();
        $department = (string) $this->request->getParam('department', '');

        $distributors = $this->distributorRepository->getActiveList($storeId);

        $allData    = [];
        $filtered   = [];
        foreach ($distributors as $distributor) {
            $item = [
                'id'         => $distributor->getId(),
                'name'       => $distributor->getName(),
                'address'    => $distributor->getAddress(),
                'city'       => $distributor->getCity(),
                'department' => $distributor->getDepartment(),
                'phone'      => $distributor->getPhone(),
                'email'      => $distributor->getEmail(),
            ];
            $allData[] = $item;

            if ($department !== '' && $distributor->getDepartment() === $department) {
                $filtered[] = $item;
            }
        }

        $resultData = ($department !== '' && count($filtered) > 0) ? $filtered : $allData;

        return $result->setData(['distributors' => $resultData]);
    }
}
