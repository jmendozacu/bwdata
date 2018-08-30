<?php

namespace Bakeway\ProductApi\Ui\DataProvider;

class Attributes extends \Magento\ConfigurableProduct\Ui\DataProvider\Attributes
{
    const NON_USED_ATTRIBUTES = ['cake_flavour', 'cake_weight', 'cake_ingredients'];
    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $items = [];
        $skippedItems = 0;
        foreach ($this->getCollection()->getItems() as $attribute) {
            if (
                $this->configurableAttributeHandler->isAttributeApplicable($attribute) &&
                in_array($attribute->getData('attribute_code'), self::NON_USED_ATTRIBUTES)
            ) {
                $items[] = $attribute->toArray();
            } else {
                $skippedItems++;
            }
        }
        return [
            'totalRecords' => $this->collection->getSize() - $skippedItems,
            'items' => $items
        ];
    }
}