<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Webkul\Marketplace\Model\ControllersRepository;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var ControllersRepository
     */
    private $controllersRepository;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ControllersRepository $controllersRepository
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ControllersRepository $controllersRepository,
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->controllersRepository = $controllersRepository;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        /**
         * insert marketplace controller's data
         */
        $data = [];
        if (!count($this->controllersRepository->getByPath('marketplace/account/dashboard'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/account/dashboard',
                'label' => 'Marketplace Dashboard',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/account/editprofile'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/account/editprofile',
                'label' => 'Seller Profile',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/product_attribute/new'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/product_attribute/new',
                'label' => 'Create Attribute',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/product/add'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/product/add',
                'label' => 'New Products',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/product/productlist'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/product/productlist',
                'label' => 'My Products List',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/transaction/history'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/transaction/history',
                'label' => 'My Transaction List',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/order/shipping'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/order/shipping',
                'label' => 'Manage Print PDF Header Info',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/order/history'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/order/history',
                'label' => 'My Order History',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (count($data)) {
            $setup->getConnection()
                ->insertMultiple($setup->getTable('marketplace_controller_list'), $data);
        }

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'mp_product_cart_limit',
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Product Purchase Limit for Customer',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => 'simple,configurable,bundle',
                'frontend_class' => 'validate-zero-or-greater',
                'note' => 'Not applicable on downloadable and virtual product.'
            ]
        );

        if (version_compare($context->getVersion(), '2.1.21', '<')) {
            $resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
            $connection = $resources->getConnection();

            $data = array(31 => 3.8, 633 => 3.8, 634 => 3.7, 636 => 3.6, 637 => 3, 638 => 4, 639 => 3.6, 640 => 3.7, 641 => 3.4, 643 => 4.3, 645 => 4.4, 646 => 4.2, 647 => 3.7, 648 => 4, 649 => 4.4, 650 => 4, 654 => 4, 656 => 4.6, 657 => 4, 658 => 4.1, 660 => 3.7, 662 => 4.9, 663 => 4, 664 => 3.5, 666 => 4.9, 668 => 3.9, 669 => 3.5, 671 => 4.5, 673 => 4.5, 674 => 4.8, 675 => 4.8, 676 => 3.5, 678 => 4, 679 => 5, 680 => 3.7, 682 => 3.9, 683 => 4.8, 684 => 3.4, 686 => 4.6, 717 => 4.1, 741 => 4.4, 742 => 4.5, 782 => 3.6, 784 => 4.4, 789 => 3.7, 816 => 3.6, 826 => 4, 830 => 5.1, 844 => 3.6, 847 => 3.8, 850 => 4.2, 853 => 4.8, 861 => 3.7, 870 => 3.9, 873 => 3.5, 875 => 3.8, 897 => 4, 898 => 3.5, 899 => 4.4, 913 => 3.9, 931 => 3.6, 937 => 3.6, 939 => 3.9, 941 => 3.6, 946 => 3.9, 967 => 4.9, 1030 => 3.8, 1032 => 4.4, 1069 => 3.1, 1070 => 3.4, 1071 => 4, 1095 => 3.8, 1098 => 4.1, 1110 => 3.8, 1111 => 4.2, 1112 => 3, 1151 => 3.9, 1164 => 4.1, 1167 => 4.1, 1176 => 4.1, 1196 => 3.8, 1242 => 3.6, 1256 => 4.8, 1259 => 3.7, 1260 => 4.4, 1265 => 3.9, 1266 => 3.8, 1267 => 3.1, 1271 => 3.6, 1303 => 4.3, 1319 => 3.3, 1374 => 3.6, 1376 => 5, 1452 => 4.1, 1508 => 4.3, 1637 => 4.1, 1680 => 4.1, 1691 => 4.1, 1699 => 4.1, 1700 => 4.2, 1703 => 4.1, 1711 => 4.1, 1715 => 3.6, 1716 => 4.2, 1728 => 4.3, 1784 => 4.1, 1850 => 4.1, 1954 => 4.1, 1989 => 4.2, 2001 => 4, 2017 => 4.4, 2194 => 4.4, 2260 => 4.3, 1167 => 4.2, 1196 => 3.8, 1242 => 3.6, 1256 => 4.8, 1259 => 3.3, 1260 => 4.1, 1265 => 3.6, 1266 => 3.8, 1267 => 2.8, 1271 => 3.6, 1303 => 4.1, 1319 => 4, 1374 => 3.6, 1376 => 5, 1452 => 4, 1508 => 4.3, 1637 => 4.2, 1680 => 0, 1691 => 4.1, 1699 => 3.8, 1700 => 0, 1703 => 4.6, 1711 => 3.9, 1715 => 3.6, 1716 => 4, 1728 => 4.2, 1773 => 4, 1784 => 3.8, 1850 => 3.1, 1954 => 3.1, 1989 => 4.2, 2001 => 4, 2017 => 4.2, 2194 => 4.4, 2260 => 4.2);

            foreach ($data as $key => $value) {
                $select = $connection->select()
                    ->from(
                        ['o' => $resources->getTableName('marketplace_feedbackcount')]
                    )->where('o.seller_id=?', $key);

                $result = $connection->fetchAll($select);
                $initialUserCount = 100;
                $initialFeedbackCount = $value * $initialUserCount;
                if (empty($result)) {
                    $sql = "INSERT INTO " . $resources->getTableName('marketplace_feedbackcount') . "(seller_id, feedback_count, avg_rating, user_count) VALUES (" . $key . ", " . $initialFeedbackCount . ", " . $value . ", " . $initialUserCount . ");";
                } else {
                    $sql = "UPDATE " . $resources->getTableName('marketplace_feedbackcount') . " SET feedback_count = " . $initialFeedbackCount . ", avg_rating = " . $value . ", user_count=" . $initialUserCount." , created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE seller_id = ". $key .";";
                }
                $connection->query($sql);

                $select2 = $connection->select()
                    ->from(
                        ['o' => $resources->getTableName('marketplace_userdata')]
                    )->where('o.seller_id=?', $key);

                $result2 = $connection->fetchAll($select2);

                if (!empty($result2)) {
                    $sql2 = "UPDATE " . $resources->getTableName('marketplace_userdata') . " SET average_ratings = " . $value . " WHERE seller_id = ". $key .";";
                    $connection->query($sql2);
                }
            }
        }

        if (version_compare($context->getVersion(), '2.1.23', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'advance_order_intimation_unit',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Advance intimation unit',
                    'input' => 'select',
                    'note' => 'Advance intimation unit',
                    'class' => '',
                    'source' => 'Webkul\Marketplace\Model\Config\Source\Options',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '1',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'option' => [
                        'values' => [],
                    ]
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.25', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $entityTypeId = 4; // Find these in the eav_entity_type table
            $eavSetup->removeAttribute($entityTypeId, 'advance_order_intimation_unit');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'advance_order_intimation_unit',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Advance intimation unit',
                    'input' => 'select',
                    'note' => 'Advance intimation unit',
                    'class' => '',
                    'source' => 'Webkul\Marketplace\Model\Config\Source\Options',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '1',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'option' => [
                        'values' => [],
                    ]
                ]
            );
        }




        $setup->endSetup();
    }
}
