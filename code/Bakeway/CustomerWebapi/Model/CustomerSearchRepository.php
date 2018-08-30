<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_CustomerWebapi
 * @author    Bakeway
 */

namespace Bakeway\CustomerWebapi\Model;

use Bakeway\CustomerWebapi\Api\CustomerSearchRepositoryInterface;
use Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationCollectionFactory;
use Bakeway\Partnerlocations\Model\ResourceModel\PartnerSubLocations\CollectionFactory as SubLocationCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;

/**
 * Class CustomerSearchRepository
 * @package Bakeway\CustomerWebapi\Model
 */
class CustomerSearchRepository implements CustomerSearchRepositoryInterface
{
    /**
     * available city ids
     * 1 Pune
     * 3 Bangalore
     */
    const CITY_IDS = [1, 3];

    /**
     * @var SellerCollectionFactory
     */
    protected $sellerCollection;

    /**
     * @var LocationCollectionFactory
     */
    protected $locationCollection;

    /**
     * @var SubLocationCollectionFactory
     */
    protected $subLocationCollection;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * CustomerSearchRepository constructor.
     * @param SellerCollectionFactory $sellerCollection
     * @param LocationCollectionFactory $locationCollection
     * @param ProductApiHelper $productApiHelper
     * @param SubLocationCollectionFactory $subLocationCollection
     */
    public function __construct(
        SellerCollectionFactory $sellerCollection,
        LocationCollectionFactory $locationCollection,
        ProductApiHelper $productApiHelper,
        SubLocationCollectionFactory $subLocationCollection
    ) {
        $this->sellerCollection = $sellerCollection;
        $this->locationCollection = $locationCollection;
        $this->productApiHelper = $productApiHelper;
        $this->subLocationCollection = $subLocationCollection;
    }

    /**
     * Main search by locality and bakery
     *
     * @param int $cityId
     * @param string|null $searchTerm
     * @return array
     * @throws LocalizedException
     */
    public function search($cityId, $searchTerm = null) {
        if (!in_array($cityId, self::CITY_IDS)) {
            throw new LocalizedException(__('City not found'));
        }
        if ($searchTerm == null || $searchTerm == '') {
            throw new LocalizedException(__('Search term can not be null'));
        }
        $result = [];
        $sellerCollection = $this->sellerCollection->create()
                            ->addFieldToSelect(['seller_id', 'business_name', 'is_conglomerate'])
                            ->addFieldToFilter('store_city', $cityId)
                            ->addFieldToFilter('business_name', ['like'=>"%$searchTerm%"])
                            ->setPageSize(5)
                            ->setCurPage(1);
        $sellerIds = $sellerCollection->getColumnValues('seller_id');
        $sellerData = [];
        if ($sellerCollection->count() >= 1) {
            $sellerUrlArray = $this->productApiHelper->getSellerUrlListArray($sellerIds, $cityId);
            $i=0;
            foreach ($sellerCollection as $seller) {
                $sellerData[$i]['business_name'] = $seller->getData('business_name');
                $sellerData[$i]['seller_id'] = $seller->getData('seller_id');
                $url = null;
                if ($seller->getData('is_conglomerate') != 1) {
                    if (isset($sellerUrlArray[$seller->getSellerId()])) {
                        $url = $sellerUrlArray[$seller->getSellerId()];
                    }
                }
                $sellerData[$i]['seo_url'] = $url;
                $i++;
            }
        }
        $result['bakeries'] = $sellerData;

        $subLocCollection = $this->subLocationCollection->create()
                    ->addFieldToSelect(['city_id', 'area_name', 'latitude', 'longitude'])
                    ->addFieldToFilter('city_id', $cityId)
                    ->addFieldToFilter('area_name', ['like'=>"%$searchTerm%"])
                    ->setPageSize(5)
                    ->setCurPage(1);

        $result['locations'] = $subLocCollection->getData();

        return json_decode(json_encode($result));
    }
}