<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare( strict_types=1 );

namespace Olegnax\Athlete2\Plugin\Frontend\Magento\Swatches\Block\Product\Renderer\Listing;

use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable as Subject;
use Magento\Swatches\Helper\Data as SwatchData;

class Configurable {
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;
    /**
     * @var Subject
     */
    protected $subject;
    /**
     * @var SwatchData
     */
    protected $swatchHelper;

    public function __construct(
        SwatchData $swatchHelper,
        JsonSerializer $serializer
    ) {


        $this->swatchHelper = $swatchHelper;
        $this->jsonSerializer = $serializer;
    }

    public function afterGetJsonConfig(
        Subject $subject,
        $result
    ) {
        if ( ! empty( $subject->getRequest()->getQuery()->toArray() ) ) {
            $data = $this->jsonSerializer->unserialize( $result );
            if ( isset( $data['preSelectedGallery'] ) ) {
                if ( empty( $data['preSelectedGallery'] ) ) {
                    $data['preSelectedGallery'] = false;
                } else {
                    $this->subject = $subject;
                    $data['preSelectedId'] = $this->getProductVariation(
                        $subject->getProduct(),
                        $subject->getRequest()->getQuery()->toArray()
                    );
                }
            }
            $result = $this->jsonSerializer->serialize( $data );
        }

        return $result;
    }

    private function getProductVariation(
        Product $configurableProduct,
        array $additionalAttributes = []
    ) {
        $configurableAttributes = $this->getLayeredAttributesIfExists($configurableProduct, $additionalAttributes);
        if (!$configurableAttributes) {
            return 0;
        }

        $product = $this->swatchHelper->loadVariationByFallback($configurableProduct, $configurableAttributes);

        return $product ? $product->getId() : 0;
    }
    private function getLayeredAttributesIfExists(Product $configurableProduct, array $additionalAttributes)
    {
        $configurableAttributes = $this->swatchHelper->getAttributesFromConfigurable($configurableProduct);

        $layeredAttributes = [];

        $configurableAttributes = array_map(
            function ($attribute) {
                return $attribute->getAttributeCode();
            },
            $configurableAttributes
        );

        $commonAttributeCodes = array_intersect(
            $configurableAttributes,
            array_keys($additionalAttributes)
        );

        foreach ($commonAttributeCodes as $attributeCode) {
            $layeredAttributes[$attributeCode] = $additionalAttributes[$attributeCode];
        }

        return $layeredAttributes;
    }
}
