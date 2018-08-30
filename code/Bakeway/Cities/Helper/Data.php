<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Cities
 * @author    Bakeway
 */

namespace Bakeway\Cities\Helper;

use Bakeway\Cities\Model\ResourceModel\Cities\CollectionFactory as CitiesCollection;

/**
 * Bakeway Cities Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DEFAULT_CITY = 'Pune';
    /**
     * @var CitiesCollection
     */
    protected $cityData;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        CitiesCollection $cityData
    )
    {
        $this->cityData = $cityData;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getCitiesOptionArray()
    {
       $cityCollection = $this->cityData->create()
            ->addFieldToFilter('is_active', 1);
        $options = [];
        $options[] = ['label' => __('---Please Select---'), 'value' => ''];

        foreach ($cityCollection as $city) {
            if ($city->getName() == self::DEFAULT_CITY):
                'selected=selected';
            endif;
            $options[] = ['label' => $city->getName(), 'value' => $city->getId()];
        }
        return $options;

    }

    /**
     * @param string $name
     * @return bool|int
     */
    public function getCityIdByName($name)
    {
        $cityCollection = $this->cityData->create();
        $cityCollection->getSelect()->where("Match(main_table.name) AGAINST ('".$name."' IN BOOLEAN MODE)");
        $cityId = $cityCollection->getFirstItem()->getId();
        if ($cityId) {
            return $cityId;
        } else {
            return false;
        }
    }

    public function getCityNameById($id)
    {
        $cityName = $this->cityData->create()
            ->addFieldToFilter('id', $id)
            ->getFirstItem()
            ->getName();

        return $cityName;
    }


    /**
     * @return array
     */
    public function getCitiesOptions()
    {
        $cityCollection = $this->cityData->create()
             ->addFieldToFilter('is_active', 1);

        $options = [];

        foreach ($cityCollection as $city) {

           $options[$city->getId()] = $city->getName();
        }

       return $options;


    }
}