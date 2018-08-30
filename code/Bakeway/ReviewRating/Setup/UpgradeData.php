<?php
/**
 *
 * Copyright © 2016 Medma. All rights reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 */
 
namespace Bakeway\ReviewRating\Setup;
 
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Review\Model\Rating as Rating;
use Symfony\Component\Config\Definition\Exception\Exception;
use Bakeway\ReviewRating\Helper\Data as ReviewRatinghelper;
use Magento\Store\Model\System\Store  as Storemodel;

class UpgradeData implements UpgradeDataInterface
{
    

    protected $customerSetupFactory;

    protected $rating;

    /**
     * @var ReviewRatinghelper
     */
    protected $reviewRatinghelper;
    /**
     * @var Storemodel
     */
    protected $systemStore;

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

     

        $installer = $setup;
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.2', '<')) {

            $codeArray = [reviewRatinghelper::ORDER_ENTITY_CODE, reviewRatinghelper::SELLER_ENTITY_CODE];
            foreach ($codeArray as $ratingcode) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $ratingObj = $objectManager->create('Magento\Review\Model\Rating\Entity');
                $ratingObj->setEntityCode($ratingcode);

                try {
                    $ratingObj->save();
                } catch (Exception $e) {
                    echo $e->getMessage();
                }

            }
        }

        if (version_compare($context->getVersion(), '1.0.5', '<')) {

            $codeArray = [reviewRatinghelper::BAKEWAY_ENTITY_CODE];
            foreach ($codeArray as $ratingcode) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $ratingObj = $objectManager->create('Magento\Review\Model\Rating\Entity');
                $ratingObj->setEntityCode($ratingcode);

                try {
                    $ratingObj->save();
                } catch (Exception $e) {
                    echo $e->getMessage();
                }

            }
        }


        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            

        }

        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            //Fill table review/review_entity
            $reviewEntityCodes = [
                ReviewRatinghelper::REVIEW_ENTITY_SELLER,
                ReviewRatinghelper::REVIEW_ENTITY_ORDER,
                ReviewRatinghelper::REVIEW_ENTITY_BKAEWAY,
            ];
            foreach ($reviewEntityCodes as $entityCode) {
                $installer->getConnection()->insert($installer->getTable('review_entity'), ['entity_code' => $entityCode]);
            }


        }

        $setup->endSetup();
    }
}
