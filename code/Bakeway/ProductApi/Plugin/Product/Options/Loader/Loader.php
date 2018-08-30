<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bakeway\ProductApi\Plugin\Product\Options\Loader;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\ConfigurableProduct\Api\Data\OptionExtensionFactory;

/**
 * Class Loader
 */
class Loader {

    /**
     * @var OptionValueInterfaceFactory
     */
    private $optionValueFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var attFactory
     */
    private $attFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_coreResource;

    /**
     * @param deliveryrangeHelper
     */
    protected $productapihelper;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavAttribute;

    protected $optionExtensionFactory;

    /**
     * @var OptionExtensionFactory
     */
    protected $optionInterfaceFactory;

    protected $webApiRequest;

    /**
     * ReadHandler constructor
     *
     * @param OptionValueInterfaceFactory $optionValueFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
    OptionValueInterfaceFactory $optionValueFactory, JoinProcessorInterface $extensionAttributesJoinProcessor, \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attFactory, \Magento\Framework\App\ResourceConnection $coreResource
    , \Bakeway\ProductApi\Helper\Data $productapiHelper, \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
     \Magento\ConfigurableProduct\Api\Data\OptionInterface $optionExtensionFactory,
      OptionExtensionFactory $optionInterfaceFactory,
        \Magento\Framework\Webapi\Rest\Request $webApiRequest
    ) {
        $this->optionValueFactory = $optionValueFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->attFactory = $attFactory;
        $this->_coreResource = $coreResource;
        $this->productapihelper = $productapiHelper;
        $this->_eavAttribute = $eavAttribute;
        $this->optionExtensionFactory = $optionExtensionFactory;
        $this->optionInterfaceFactory = $optionInterfaceFactory;
        $this->webApiRequest = $webApiRequest;
    }

    /**
     * @param ProductInterface $product
     * @return OptionInterface[]
     */
    public function aroundLoad(\Magento\ConfigurableProduct\Helper\Product\Options\Loader $subject, \Closure $proceed, ProductInterface $product) {
        $options = [];
        $requestPath = $this->webApiRequest->getPathInfo();
        if (strpos($requestPath, '/syncproducts') !== false) {
            $result = $proceed($product);
            return $result;
        }
        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $attributeCollection = $typeInstance->getConfigurableAttributeCollection($product);
        $ExtensionAttObject = $product->getExtensionAttributes(); //tmp
        $this->extensionAttributesJoinProcessor->process($attributeCollection);
        $_skus = $this->productapihelper->getChildrenSkus($product);
        $attributeId = $this->_eavAttribute->getIdByCode('catalog_product', 'cake_weight');
        $extraAttributecId = $this->_eavAttribute->getIdByCode('catalog_product', 'advance_order_intimation_unit');
        $extraAttributeArray = array($extraAttributecId);

        foreach ($attributeCollection as $attribute) {
            $values = [];
            $attributeOptions = $attribute->getOptions();


            if ($attributeId == $attribute['attribute_id']) {
                if (is_array($attributeOptions)) {
                    foreach ($attributeOptions as $option) {
                        $value = $this->optionValueFactory->create();
                        $_CheckAvaSku = $this->productapihelper->getOptionsSkus($option['value_index'], $_skus, $product);
                        if (!empty($_CheckAvaSku)) {
                            $_array = array('value' => $option['label'], 'value_index' => $option['value_index'], 'label' => $option['store_label'], 'available_skus' =>
                                $_CheckAvaSku);
                            $values[] = $_array;
                        }
                    }
                }
                array_multisort($values);
            } else {
                if (is_array($attributeOptions)) {
                    foreach ($attributeOptions as $option) {

                        /** @var \Magento\ConfigurableProduct\Api\Data\OptionValueInterface $value */
                        $value = $this->optionValueFactory->create();
                        //$_Label = $this->getAttributeLabel($option['value_index']);
                        /*
                         * check avaiable skus
                         */
                        $_CheckAvaSku = $this->productapihelper->getOptionsSkus($option['value_index'], $_skus, $product);
                        if (!empty($_CheckAvaSku)) {
                            $_array = array('value_index' => $option['value_index'], 'label' => $option['store_label'], 'available_skus' =>
                                $_CheckAvaSku);
                            $values[] = $_array;
                        }

                    }

                }
            }
            

            $extensionAttributes = $attribute->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->optionInterfaceFactory->create();
            }
            $extensionAttributes->setAttributeCode(($this->getAttributeCode($attribute['attribute_id'])));
            $attribute->setExtensionAttributes($extensionAttributes);
            $attribute->setValues($values);
            if(!in_array($attribute['attribute_id'],$extraAttributeArray)){
                $options[] = $attribute;
            }


        }

        return $options;
    }

    /**
     * @param $id
     * @return option label
     */
    public function getAttributeLabel($id) {
        $connection = $this->_coreResource->getConnection();
        $tableName = $connection->getTableName('eav_attribute_option_value'); //gives table name with prefix
        $sql = $connection->select()
                ->from($tableName, array('value'))
                ->where('option_id=' . $id);
        $result = $connection->fetchOne($sql);
        return $result;
    }

    /**
     * @param $id
     * @return option code
     */
    public function getAttributeCode($id) {
        $connection = $this->_coreResource->getConnection();
        $tableName = $connection->getTableName('eav_attribute'); //gives table name with prefix
        $sql = $connection->select()
                ->from($tableName, array('attribute_code'))
                ->where('attribute_id=' . $id);
        $result = $connection->fetchOne($sql);
        return $result;
    }

}
