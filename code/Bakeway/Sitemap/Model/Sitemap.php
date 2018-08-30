<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_Sitemap
 * @author    Bakeway
 */

namespace Bakeway\Sitemap\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as SellerProductCollectionFactory;
use \Bakeway\Sitemap\Model\ResourceModel\Catalog\Product as ProductCollectionFactory;
use \Bakeway\Partnerlocations\Model\ResourceModel\Partnerlocations\CollectionFactory as LocationCollectionFactory;
use \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection as UrlCollection;
use Bakeway\ProductApi\Helper\Data as ProductApiHelper;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Bakeway Sitemap Model.

 */
class Sitemap extends AbstractModel implements IdentityInterface
{
    /**
     * Bakeway Sitemap cache tag.
     */
    const CACHE_TAG = 'bakeway_sitemap';

    const FRONTEND_BASE_URL = "https://bakeway.com/";

    const OPEN_TAG_KEY = 'start';

    const CLOSE_TAG_KEY = 'end';

    const INDEX_FILE_PREFIX = 'sitemap';

    const TYPE_INDEX = 'sitemap';

    const TYPE_URL = 'url';

    const PATH = "/sitemap/";

    const FILENAME = "sitemap.xml";

    const PRODUCT_PRIORITY = "0.7";

    const SELLER_PRIORITY = "0.8";


    /**
     * @var string
     */
    protected $_cacheTag = 'bakeway_sitemap';

    /**
     * Prefix of model events names.
     * @var string
     */
    protected $_eventPrefix = 'bakeway_sitemap';

    /**
     * Real file path
     *
     * @var string
     */
    protected $_filePath;

    /**
     * Sitemap items
     *
     * @var array
     */
    protected $_sitemapItems = [];

    /**
     * Current sitemap increment
     *
     * @var int
     */
    protected $_sitemapIncrement = 0;

    /**
     * Sitemap start and end tags
     *
     * @var array
     */
    protected $_tags = [];

    /**
     * Number of lines in sitemap
     *
     * @var int
     */
    protected $_lineCount = 0;

    /**
     * Current sitemap file size
     *
     * @var int
     */
    protected $_fileSize = 0;

    /**
     * New line possible symbols
     *
     * @var array
     */
    private $_crlf = ["win" => "\r\n", "unix" => "\n", "mac" => "\r"];

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $_directory;

    /**
     * @var \Magento\Framework\Filesystem\File\Write
     */
    protected $_stream;

    /**
     * Sitemap data
     *
     * @var \Magento\Sitemap\Helper\Data
     */
    protected $_sitemapData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var SellerCollectionFactory
     */
    protected $sellerCollectionFactory;

    /**
     * @var SellerProductCollectionFactory
     */
    protected $sellerProductCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var LocationCollectionFactory
     */
    protected $locationCollectionFactory;

    /**
     * @var UrlCollection
     */
    protected $urlCollection;

    /**
     * @var ProductApiHelper
     */
    protected $productApiHelper;

    /**
     * Generate constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param SellerCollectionFactory $sellerCollectionFactory
     * @param SellerProductCollectionFactory $sellerProductCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LocationCollectionFactory $locationCollectionFactory
     * @param UrlCollection $urlCollection
     * @param ProductApiHelper $productApiHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Sitemap\Helper\Data $sitemapData
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $modelDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        SellerCollectionFactory $sellerCollectionFactory,
        SellerProductCollectionFactory $sellerProductCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        LocationCollectionFactory $locationCollectionFactory,
        UrlCollection $urlCollection,
        ProductApiHelper $productApiHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Sitemap\Helper\Data $sitemapData,
        \Magento\Framework\Stdlib\DateTime\DateTime $modelDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->sellerProductCollectionFactory = $sellerProductCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->urlCollection = $urlCollection;
        $this->productApiHelper = $productApiHelper;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->dateTime = $dateTime;
        $this->_sitemapData = $sitemapData;
        $this->_dateModel = $modelDate;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(\Bakeway\Sitemap\Model\ResourceModel\Sitemap::class);
    }

    /**
     * Load object data.
     *
     * @param int|null $id
     * @param string   $field
     *
     * @return $this
     */
    public function load($id, $field = null)
    {
        if ($id === null) {
            return $this->noRouteSeller();
        }

        return parent::load($id, $field);
    }

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * Check sitemap file location and permissions
     *
     * @return \Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $path = $this->getFilepath();

        /**
         * Check path is allow
         */
        if ($path && preg_match('#\.\.[\\\/]#', $path)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please define a correct path.'));
        }
        /**
         * Check exists and writable path
         */
        if (!$this->_directory->isExist($path)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Please create the specified folder "%1" before saving the sitemap.',
                    $this->_escaper->escapeHtml($this->getFilepath())
                )
            );
        }

        if (!$this->_directory->isWritable($path)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please make sure that "%1" is writable by the web-server.', $this->getFilepath())
            );
        }
        /**
         * Check allow filename
         */
        if (!preg_match('#^[a-zA-Z0-9_\.]+$#', $this->getFilename())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Please use only letters (a-z or A-Z), numbers (0-9) or underscores (_) in the filename. No spaces or other characters are allowed.'
                )
            );
        }
        if (!preg_match('#\.xml$#', $this->getFilename())) {
            $this->setFilename($this->getFilename() . '.xml');
        } else {
            $this->setFilename($this->getFilename());
        }

        $this->setFilepath(rtrim(str_replace(str_replace('\\', '/', $this->_getBaseDir()), '', $path), '/') . '/');

        return parent::beforeSave();
    }

    /**
     * Get file handler
     *
     * @return \Magento\Framework\Filesystem\File\WriteInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getStream()
    {
        if ($this->_stream) {
            return $this->_stream;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('File handler unreachable'));
        }
    }

    /**
     * Generate XML file
     *
     * @see http://www.sitemaps.org/protocol.html
     *
     * @return $this
     */
    public function generateXml()
    {
        $this->_initSitemapItems();
        /** @var $sitemapItem \Magento\Framework\DataObject */
        foreach ($this->_sitemapItems as $sitemapItem) {
            $changefreq = $sitemapItem->getChangefreq();
            $priority = $sitemapItem->getPriority();
            foreach ($sitemapItem->getCollection() as $key=>$item) {
                $xml = $this->_getSitemapRow(
                    $item,
                    '',
                    $changefreq,
                    $priority,
                    ''
                );
                if ($this->_isSplitRequired($xml) && $this->_sitemapIncrement > 0) {
                    $this->_finalizeSitemap();
                }
                if (!$this->_fileSize) {
                    $this->_createSitemap();
                }
                $this->_writeSitemapRow($xml);
                // Increase counters
                $this->_lineCount++;
                $this->_fileSize += strlen($xml);
            }
        }
        $this->_finalizeSitemap();

        if ($this->_sitemapIncrement == 1) {
            // In case when only one increment file was created use it as default sitemap
            $path = rtrim(
                    $this->getFilepath(),
                    '/'
                ) . '/' . $this->_getCurrentSitemapFilename(
                    $this->_sitemapIncrement
                );
            $destination = rtrim($this->getFilepath(), '/') . '/' . $this->getFilename();
            //echo $path."  :::   ".$destination;exit;
            $this->_directory->renameFile($path, $destination);
        } else {
            // Otherwise create index file with list of generated sitemaps
            $this->_createSitemapIndex();
        }

//        // Push sitemap to robots.txt
//        if ($this->_isEnabledSubmissionRobots()) {
//            $this->_addSitemapToRobotsTxt($this->getFilename());
//        }

        $this->setCreatedAt($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }

    /**
     * Get sitemap row
     *
     * @param string $url
     * @param null|string $lastmod
     * @param null|string $changefreq
     * @param null|string $priority
     * @param null|array $images
     * @return string
     * Sitemap images
     * @see http://support.google.com/webmasters/bin/answer.py?hl=en&answer=178636
     *
     * Sitemap PageMap
     * @see http://support.google.com/customsearch/bin/answer.py?hl=en&answer=1628213
     */
    protected function _getSitemapRow($url, $lastmod = null, $changefreq = null, $priority = null, $images = null)
    {
        $row = '<loc>' . htmlspecialchars($url) . '</loc>';
        if ($lastmod) {
            $row .= '<lastmod>' . $this->_getFormattedLastmodDate($lastmod) . '</lastmod>';
        }
        if ($changefreq) {
            $row .= '<changefreq>' . $changefreq . '</changefreq>';
        }
        if ($priority) {
            $row .= sprintf('<priority>%.1f</priority>', $priority);
        }
//        if ($images) {
//            // Add Images to sitemap
//            foreach ($images->getCollection() as $image) {
//                $row .= '<image:image>';
//                $row .= '<image:loc>' . htmlspecialchars($this->_getMediaUrl($image->getUrl())) . '</image:loc>';
//                $row .= '<image:title>' . htmlspecialchars($images->getTitle()) . '</image:title>';
//                if ($image->getCaption()) {
//                    $row .= '<image:caption>' . htmlspecialchars($image->getCaption()) . '</image:caption>';
//                }
//                $row .= '</image:image>';
//            }
//            // Add PageMap image for Google web search
//            $row .= '<PageMap xmlns="http://www.google.com/schemas/sitemap-pagemap/1.0"><DataObject type="thumbnail">';
//            $row .= '<Attribute name="name" value="' . htmlspecialchars($images->getTitle()) . '"/>';
//            $row .= '<Attribute name="src" value="' . htmlspecialchars(
//                    $this->_getMediaUrl($images->getThumbnail())
//                ) . '"/>';
//            $row .= '</DataObject></PageMap>';
//        }

        return '<url>' . $row . '</url>';
    }

    /**
     * Generate sitemap index XML file
     *
     * @return void
     */
    protected function _createSitemapIndex()
    {
        $this->_createSitemap($this->getFilename(), self::TYPE_INDEX);
        for ($i = 1; $i <= $this->_sitemapIncrement; $i++) {
            $xml = $this->_getSitemapIndexRow($this->_getCurrentSitemapFilename($i), $this->_getCurrentDateTime());
            $this->_writeSitemapRow($xml);
        }
        $this->_finalizeSitemap(self::TYPE_INDEX);
    }

    /**
     * Write sitemap row
     *
     * @param string $row
     * @return void
     */
    protected function _writeSitemapRow($row)
    {
        $this->_getStream()->write($row . PHP_EOL);
    }

    /**
     * Write closing tag and close stream
     *
     * @param string $type
     * @return void
     */
    protected function _finalizeSitemap($type = self::TYPE_URL)
    {
        if ($this->_stream) {
            $this->_stream->write(sprintf($this->_tags[$type][self::CLOSE_TAG_KEY], $type));
            $this->_stream->close();
        }

        // Reset all counters
        $this->_lineCount = 0;
        $this->_fileSize = 0;
    }

    /**
     * Get current sitemap filename
     *
     * @param int $index
     * @return string
     */
    protected function _getCurrentSitemapFilename($index)
    {
        return self::INDEX_FILE_PREFIX . '-' . $this->getStoreId() . '-' . $index . '.xml';
    }

    /**
     * Get base dir
     *
     * @return string
     */
    protected function _getBaseDir()
    {
        return $this->_directory->getAbsolutePath();
    }

    /**
     * Get current date time
     *
     * @return string
     */
    protected function _getCurrentDateTime()
    {
        return (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * Check is split required
     *
     * @param string $row
     * @return bool
     */
    protected function _isSplitRequired($row)
    {
        /** @var $helper \Magento\Sitemap\Helper\Data */
        $helper = $this->_sitemapData;
        $storeId = $this->getStoreId();
        if ($this->_lineCount + 1 > $helper->getMaximumLinesNumber($storeId)) {
            return true;
        }

        if ($this->_fileSize + strlen($row) > $helper->getMaximumFileSize($storeId)) {
            return true;
        }

        return false;
    }

    /**
     * Get sitemap index row
     *
     * @param string $sitemapFilename
     * @param null|string $lastmod
     * @return string
     */
    protected function _getSitemapIndexRow($sitemapFilename, $lastmod = null)
    {
        $url = $this->getSitemapUrl($this->getFilepath(), $sitemapFilename);
        $row = '<loc>' . htmlspecialchars($url) . '</loc>';
        if ($lastmod) {
            $row .= '<lastmod>' . $this->_getFormattedLastmodDate($lastmod) . '</lastmod>';
        }

        return '<sitemap>' . $row . '</sitemap>';
    }


    /**
     * Get store base url
     *
     * @param string $type
     * @return string
     */
    protected function _getStoreBaseUrl($type = \Magento\Framework\UrlInterface::URL_TYPE_LINK)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore($this->getStoreId());

        $isSecure = $store->isUrlSecure();

        return rtrim($store->getBaseUrl($type, $isSecure), '/') . '/';
    }

    /**
     * Get url
     *
     * @param string $url
     * @param string $type
     * @return string
     */
    protected function _getUrl($url, $type = \Magento\Framework\UrlInterface::URL_TYPE_LINK)
    {
        return $this->_getStoreBaseUrl($type) . ltrim($url, '/');
    }

    /**
     * Get media url
     *
     * @param string $url
     * @return string
     */
    protected function _getMediaUrl($url)
    {
        return $this->_getUrl($url, \Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get date in correct format applicable for lastmod attribute
     *
     * @param string $date
     * @return string
     */
    protected function _getFormattedLastmodDate($date)
    {
        return date('c', strtotime($date));
    }

    /**
     * Get Document root of Magento instance
     *
     * @return string
     */
    protected function _getDocumentRoot()
    {
        return realpath($this->_request->getServer('DOCUMENT_ROOT'));
    }

    /**
     * Get domain from store base url
     *
     * @return string
     */
    protected function _getStoreBaseDomain()
    {
        $storeParsedUrl = parse_url($this->_getStoreBaseUrl());
        $url = $storeParsedUrl['scheme'] . '://' . $storeParsedUrl['host'];

        $documentRoot = trim(str_replace('\\', '/', $this->_getDocumentRoot()), '/');
        $baseDir = trim(str_replace('\\', '/', $this->_getBaseDir()), '/');

        if (strpos($baseDir, $documentRoot) === 0) {
            //case when basedir is in document root
            $installationFolder = trim(str_replace($documentRoot, '', $baseDir), '/');
            $storeDomain = rtrim($url . '/' . $installationFolder, '/');
        } else {
            //case when documentRoot contains symlink to basedir
            $url = $this->_getStoreBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
            $storeDomain = rtrim($url, '/');
        }

        return $storeDomain;
    }

    /**
     * Get sitemap.xml URL according to all config options
     *
     * @param string $sitemapPath
     * @param string $sitemapFileName
     * @return string
     */
    public function getSitemapUrl($sitemapPath, $sitemapFileName)
    {
        return $this->_getStoreBaseDomain() . str_replace('//', '/', $sitemapPath . '/' . $sitemapFileName);
    }

    /**
     * Create new sitemap file
     *
     * @param null|string $fileName
     * @param string $type
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _createSitemap($fileName = null, $type = self::TYPE_URL)
    {
        if (!$fileName) {
            $this->_sitemapIncrement++;
            $fileName = $this->_getCurrentSitemapFilename($this->_sitemapIncrement);
        }

        $path = rtrim($this->getFilepath(), '/') . '/' . $fileName;
        $this->_stream = $this->_directory->openFile($path);

        $fileHeader = sprintf($this->_tags[$type][self::OPEN_TAG_KEY], $type);
        $this->_stream->write($fileHeader);
        $this->_fileSize = strlen($fileHeader . sprintf($this->_tags[$type][self::CLOSE_TAG_KEY], $type));
    }

    /**
     * Initialize sitemap items
     *
     * @return void
     */
    protected function _initSitemapItems()
    {
        /** @var $helper \Magento\Sitemap\Helper\Data */
        $helper = $this->_sitemapData;
        $storeId = $this->getStoreId();

        $this->_sitemapItems[] = new \Magento\Framework\DataObject(
            [
                'changefreq' => 'monthly',
                'priority' => self::SELLER_PRIORITY,
                'collection' => $this->getSitemapItemCollection('seller'),
            ]
        );

        $this->_sitemapItems[] = new \Magento\Framework\DataObject(
            [
                'changefreq' => 'monthly',
                'priority' => self::PRODUCT_PRIORITY,
                'collection' => $this->getSitemapItemCollection('product'),
            ]
        );

        $this->_tags = [
            self::TYPE_INDEX => [
                self::OPEN_TAG_KEY => '<?xml version="1.0" encoding="UTF-8"?>' .
                    PHP_EOL .
                    '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
                    PHP_EOL,
                self::CLOSE_TAG_KEY => '</sitemapindex>',
            ],
            self::TYPE_URL => [
                self::OPEN_TAG_KEY => '<?xml version="1.0" encoding="UTF-8"?>' .
                    PHP_EOL .
                    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' .
                    ' xmlns:content="http://www.google.com/schemas/sitemap-content/1.0"' .
                    ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' .
                    PHP_EOL,
                self::CLOSE_TAG_KEY => '</urlset>',
            ],
        ];
    }

    /**
     * Get Sitemap Item Collection
     * @param string $item (product/seller)
     * @return mixed
     */
    public function getSitemapItemCollection($item)
    {
        $items = [];
        $productUrls = [];
        $sellerUrls = [];

        /**
         * Code for getting product Urls
         */
        if ($item == 'product') {
            $locs = $this->getConglomerateSellerArray();
            $products = $this->productCollectionFactory->getCollection();
            foreach ($products as $product) {
                $isConglo = $product->getData('is_conglomerate');
                $url = $this->getProductSeoUrl($product->getData('url'), $product->getData('entity_id'));
                if ($isConglo == 1 && isset($locs[$product->getData('seller_id')])) {
                    foreach ($locs[$product->getData('seller_id')] as $key => $value) {
                        $productUrls[] = self::FRONTEND_BASE_URL . $url . '?store=' . $value;
                    }
                } else {
                    $productUrls[] = self::FRONTEND_BASE_URL . $url;
                }
            }
            $items['products'] = $productUrls;
            return $productUrls;
        }

        /**
         * Code for getting store Urls
         */
        if ($item == 'seller') {
            $cityWiseSellers = $this->getCityWiseSellerArray();
            $rawSellerUrls = [];
            $sellerArray = [];
            foreach ($cityWiseSellers as $sellers) {
                if (!empty($sellers['seller_ids']) && isset($sellers['seller_ids'])) {
                    $sellerIdsArray = explode(',', $sellers['seller_ids']);
                    $sellerArray = array_merge($sellerArray, $sellerIdsArray);
                    $urls = $this->productApiHelper->getSellerUrlListArray($sellerIdsArray, $sellers['store_city']);
                    $rawSellerUrls[] = $urls;
                }
            }
            $congloSellers = $this->getConglomerateSellerArray($sellerArray);

            foreach ($rawSellerUrls as $cityWiseUrls) {
                foreach ($cityWiseUrls as $sellerId => $sellerUrl) {
                    $url = $sellerUrl;
                    if (isset($congloSellers[$sellerId])) {
                        foreach ($congloSellers[$sellerId] as $key => $value) {
                            $sellerUrls[] = self::FRONTEND_BASE_URL . $url . '?store=' . $value;
                        }
                    } else {
                        $sellerUrls[] = self::FRONTEND_BASE_URL . $url;
                    }
                }
            }
            $items['sellers'] = $sellerUrls;
            return $sellerUrls;
        }
        return $items;
    }

    public function getConglomerateSellerArray(array $sellerIds = null) {
        $result = [];
        $locationCollection = $this->locationCollectionFactory->create()
                ->addFieldToSelect(['seller_id','store_unique_name']);
        if ($sellerIds !== null) {
            $locationCollection->addFieldToFilter('main_table.seller_id', ['in'=>$sellerIds]);
        }
        $locationCollection->getSelect()->joinLeft(
            ['mp_udata' => $locationCollection->getTable('marketplace_userdata')],
            'main_table.seller_id=mp_udata.seller_id',
            ['is_conglomerate']
        );
        $locationCollection->getSelect()->where('mp_udata.is_seller = ?', 1);
        $locationCollection->getSelect()->where('mp_udata.is_conglomerate = ?', 1);
        $locationCollection->getSelect()->where('mp_udata.is_live_ready = ?', 1);

        foreach ($locationCollection as $location) {
            $result[$location->getData('seller_id')][] = $location->getData('store_unique_name');
        }
        return $result;
    }

    public function getProductSeoUrl($url, $productId) {
        if ($url !== null) {
            $sellerCity = "pune";
            if ($sellerCity !== null) {
                $cityString = preg_replace('#[^0-9a-z]+#i', '-', strtolower($sellerCity));

                $pos = strpos($url, $cityString . "-");
                if ($pos !== false) {
                    $url = substr_replace($url, $cityString . "/", $pos, strlen($cityString . "-"));
                } else {
                    $url = str_replace($cityString . "-", $cityString . "/", $url);
                }
            }
        }
        return $url;
    }

    public function getCityWiseSellerArray() {
        $result = [];
        $partnerCollection = $this->sellerCollectionFactory->create()
            ->addFieldToSelect(['store_city'])
            ->addFieldToFilter('is_seller', 1)
            ->addFieldToFilter('is_live_ready', 1);
        $partnerCollection->getSelect()->columns('GROUP_CONCAT(seller_id SEPARATOR \', \') as seller_ids')
        ->group('store_city');
        $result = $partnerCollection->getData();
        return $result;
    }

    public function getStoreId() {
        return \Bakeway\Sitemap\Model\ResourceModel\Catalog\Product::STORE_ID;
    }

    public function getFilepath() {
        return self::PATH;
    }

    public function getFilename() {
        return self::FILENAME;
    }
}
