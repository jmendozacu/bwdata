<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_SubLocations
 * @author    Bakeway
 */

namespace Bakeway\SubLocations\Api;


interface SubLocationsRepositoryInterface
{
    /**
     * Get Cities List.
     * @return mixed
     */
    public function getCityList();

    /**
     * Get Sub Locations List
     * @param int $cityId
     * @param  string $search
     * @return mixed
     */
    public function getSubLocalityList($cityId, $search);
}