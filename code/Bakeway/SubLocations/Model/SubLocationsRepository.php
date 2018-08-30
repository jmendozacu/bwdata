<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_SubLocations
 * @author    Bakeway
 */

namespace Bakeway\SubLocations\Model;

use Bakeway\SubLocations\Api\SubLocationsRepositoryInterface as SubLocationsInterface;
use Bakeway\Cities\Model\ResourceModel\Cities\CollectionFactory as CitiesCollection;
use Bakeway\SubLocations\Model\ResourceModel\SubLocations\CollectionFactory as SubLocationsCollection;

class SubLocationsRepository implements SubLocationsInterface
{
    /**
     * @var CitiesCollection
     */
    protected $citiesCollection;

    /**
     * @var SubLocationsCollection
     */
    protected $subLocationsCollection;

    /**
     * SubLocationsRepository constructor.
     * @param CitiesCollection $citiesCollection
     * @param SubLocationsCollection $subLocationsCollection
     */
    public function __construct(
        CitiesCollection $citiesCollection,
        SubLocationsCollection $subLocationsCollection
    )
    {
        $this->citiesCollection = $citiesCollection;
        $this->subLocationsCollection = $subLocationsCollection;
    }

    /**
     * Get Cities List.
     * @return mixed
     */
    public function getCityList()
    {
        $cityCollection = $this->citiesCollection->create()
            ->addFieldToSelect(['id','name','short_code'])
            ->addFieldToFilter('is_active', 1);
        $result = $cityCollection->getData();
        return $result;
    }

    /**
     * Get Sub Locations List
     * @param int $cityId
     * @param string $search
     * @return mixed
     */
    public function getSubLocalityList($cityId, $search)
    {
        $collection = $this->subLocationsCollection->create()
            ->addFieldToSelect(['id','area_name','latitude','longitude'])
            ->addFieldToFilter('city_id', 1)
            ->addFieldToFilter('area_name',['like'=>"%$search%"])
            ->setCurPage(1)
            ->setPageSize(5);
        $result = $collection->getData();
        return $result;
    }
}