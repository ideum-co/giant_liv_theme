<?php
declare(strict_types=1);

namespace Giant\Checkout\Controller\Adminhtml\Distributor;

use Giant\Checkout\Api\Data\DistributorInterface;
use Giant\Checkout\Api\DistributorRepositoryInterface;
use Giant\Checkout\Model\Config\Source\Departments;
use Giant\Checkout\Model\DistributorFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Giant_Checkout::distributor';

    public function __construct(
        Context $context,
        private readonly DistributorRepositoryInterface $distributorRepository,
        private readonly DistributorFactory             $distributorFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $redirect = $this->resultRedirectFactory->create();

        if (empty($data)) {
            return $redirect->setPath('*/*/index');
        }

        $id = isset($data[DistributorInterface::ENTITY_ID])
            ? (int) $data[DistributorInterface::ENTITY_ID]
            : 0;

        try {
            $distributor = ($id > 0)
                ? $this->distributorRepository->getById($id)
                : $this->distributorFactory->create();

            $distributor->setName($data[DistributorInterface::NAME] ?? '');
            $distributor->setAddress($data[DistributorInterface::ADDRESS] ?? null);
            $distributor->setCity($data[DistributorInterface::CITY] ?? null);
            $deptValue = $data[DistributorInterface::DEPARTMENT] ?? null;
            if ($deptValue !== null && $deptValue !== '' && !in_array($deptValue, Departments::getDepartments(), true)) {
                $this->messageManager->addErrorMessage(__('Departamento no válido.'));
                return $redirect->setPath('*/*/edit', [
                    DistributorInterface::ENTITY_ID => $id ?: null,
                ]);
            }
            $distributor->setDepartment($deptValue);
            $distributor->setPhone($data[DistributorInterface::PHONE] ?? null);
            $distributor->setEmail($data[DistributorInterface::EMAIL] ?? null);
            $distributor->setIsActive((int) ($data[DistributorInterface::IS_ACTIVE] ?? 1));
            $distributor->setSortOrder((int) ($data[DistributorInterface::SORT_ORDER] ?? 0));

            // Store IDs: save as comma-separated string
            if (isset($data[DistributorInterface::STORE_IDS]) && is_array($data[DistributorInterface::STORE_IDS])) {
                $distributor->setStoreIds(implode(',', $data[DistributorInterface::STORE_IDS]));
            } else {
                $distributor->setStoreIds($data[DistributorInterface::STORE_IDS] ?? null);
            }

            $this->distributorRepository->save($distributor);
            $this->messageManager->addSuccessMessage(__('Distribuidor guardado correctamente.'));

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $redirect->setPath('*/*/edit', [
                    DistributorInterface::ENTITY_ID => $distributor->getId(),
                ]);
            }
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Distribuidor no encontrado.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Error al guardar el distribuidor.'));
            return $redirect->setPath('*/*/edit', [
                DistributorInterface::ENTITY_ID => $id ?: null,
            ]);
        }

        return $redirect->setPath('*/*/index');
    }
}
