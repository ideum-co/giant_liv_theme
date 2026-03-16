<?php
declare(strict_types=1);

namespace Giant\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Departments implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $departments = self::getDepartments();
        $options = [['value' => '', 'label' => __('-- Seleccionar departamento --')]];

        foreach ($departments as $dept) {
            $options[] = ['value' => $dept, 'label' => $dept];
        }

        return $options;
    }

    public static function getDepartments(): array
    {
        return [
            'Amazonas',
            'Antioquia',
            'Arauca',
            'Atlántico',
            'Bolívar',
            'Boyacá',
            'Caldas',
            'Caquetá',
            'Casanare',
            'Cauca',
            'Cesar',
            'Chocó',
            'Córdoba',
            'Cundinamarca',
            'Guainía',
            'Guaviare',
            'Huila',
            'La Guajira',
            'Magdalena',
            'Meta',
            'Nariño',
            'Norte de Santander',
            'Putumayo',
            'Quindío',
            'Risaralda',
            'San Andrés y Providencia',
            'Santander',
            'Sucre',
            'Tolima',
            'Valle del Cauca',
            'Vaupés',
            'Vichada',
        ];
    }
}
