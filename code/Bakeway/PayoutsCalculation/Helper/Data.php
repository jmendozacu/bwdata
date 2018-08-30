<?php
/**
 * Bakeway
 *
 * @category  Bakeway
 * @package   Bakeway_PayoutsCalculation
 * @author    Bakeway
 */

namespace Bakeway\PayoutsCalculation\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime as StoreDateTime;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Thai\S3\Model\MediaStorage\File\Storage\S3 as ThaiBucketStorage;
use Aws\S3\Exception\S3Exception;
use Thai\S3\Helper\Data as ThaiHelper;
use Magento\MediaStorage\Helper\File\Media as MediaHelper;
use \Magento\Sales\Model\OrderRepository as SalesOrderRepository;
use Bakeway\Partnerlocations\Model\Partnerlocations;
use Bakeway\Cities\Model\Cities;

class Data extends AbstractHelper
{
    /**
     * calculations config paths
     */
    const TCS_PERCENTAGE = 'payouts_calculation/calculations_tax_percent/tcs_percentage';
    const DELIVERY_FEE_TAX_PERCENTAGE = 'payouts_calculation/calculations_tax_percent/delivery_fee_tax_percentage';
    const CGST_PERCENTAGE = 'payouts_calculation/calculations_tax_percent/cgst_percentage';
    const SGST_PERCENTAGE = 'payouts_calculation/calculations_tax_percent/sgst_percentage';
    const IGST_PERCENTAGE = 'payouts_calculation/calculations_tax_percent/igst_percentage';
    const ORDER_PG_PERCENTAGE = 'payouts_calculation/calculations_tax_percent/order_payment_gateway_percentage';

    const NODAL_ACCOUNT_NUMBER = 'payouts_calculation/bakeway_account_details/nodal_account_number';
    const NODAL_ACCOUNT_IFSC = 'payouts_calculation/bakeway_account_details/nodal_account_ifsc';
    const NODAL_ACCOUNT_NAME = 'payouts_calculation/bakeway_account_details/nodal_account_name';
    const CREDIT_ACCOUNT_NUMBER = 'payouts_calculation/bakeway_account_details/credit_account_number';
    const CREDIT_ACCOUNT_IFSC = 'payouts_calculation/bakeway_account_details/credit_account_ifsc';
    const CREDIT_ACCOUNT_NAME = 'payouts_calculation/bakeway_account_details/credit_account_name';
    const CREDIT_ACCOUNT_EMAIL = 'payouts_calculation/bakeway_account_details/credit_account_email';
    const CREDIT_ACCOUNT_MOBILE = 'payouts_calculation/bakeway_account_details/credit_account_mobile';

    const OUTWORD_S3_URL = 'payouts_calculation/bakeway_account_details/bakeway_s3_outword';
    const OUTWORD_BUCKET_NAME = 'outwordsheetbucket';

    const MODE_RTGS = 'R';
    const MODE_NEFT = 'N';
    const BAKWAY_CODE = 'BKW';

    const TRANS_STATUS_UNPAID = 0;
    const TRANS_STATUS_PAID = 1;
    const TRANS_STATUS_PROCESSING = 2;
    const TRANS_STATUS_FAILED = 3;

    /**
     * Bakeway operational state
     */
    const BAKEWAY_OPERATIONAL_STATE = 'Maharashtra';
    
    /**
     * Bakeway operational cities
     */
    const BAKEWAY_OPERATIONAL_CITIES = array('pune');

    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \PHPExcel
     */
    protected $excelParser;

    /**
     * @var StoreDateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var ThaiBucketStorage
     */
    protected $thaiBucketStorage;

    /**
     * @var ThaiHelper
     */
    protected $thaiHelper;

    /**
     * @var MediaHelper
     */
    protected $mediaHelper;

    /**
     * @var \Aws\S3\S3Client
     */
    protected $payoutBucketClient;

    protected $salesOrderRepository;
    
    /**
     * Partner Locations
     * @var Bakeway\Partnerlocations\Model\Partnerlocations 
     */
    protected $partnerLocation;
    
    /**
     * Bake way cities
     * @var Bakeway\Cities\Model\Cities
     */
    protected $cities;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface        $objectManager
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param \PHPExcel $excel
     * @param StoreDateTime $date
     * @param \Magento\Framework\Filesystem $filesystem
     * @param CustomerRepositoryInterface $customerRepository
     * @param MarketplaceHelper $marketplaceHelper
     * @param ThaiBucketStorage $thaiBucketStorage
     * @param ThaiHelper $thaiHelper
     * @param MediaHelper $mediaHelper
     * @param SalesOrderRepository $salesOrderRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        OrderCollectionFactory $orderCollectionFactory,
        \PHPExcel $excel,
        StoreDateTime $date,
        \Magento\Framework\Filesystem $filesystem,
        CustomerRepositoryInterface $customerRepository,
        MarketplaceHelper $marketplaceHelper,
        ThaiBucketStorage $thaiBucketStorage,
        ThaiHelper $thaiHelper,
        MediaHelper $mediaHelper,
        SalesOrderRepository $salesOrderRepository,
        Partnerlocations $partnerLocation,
        Cities $cities
    ) {
        $this->objectManager = $objectManager;
        parent::__construct($context);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->excelParser = $excel;
        $this->date = $date;
        $this->fileSystem = $filesystem;
        $this->customerRepository = $customerRepository;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->thaiBucketStorage = $thaiBucketStorage;
        $this->thaiHelper = $thaiHelper;
        $this->mediaHelper = $mediaHelper;
        $this->salesOrderRepository = $salesOrderRepository;
        $this->partnerLocation = $partnerLocation;
        $this->cities = $cities;
        
        $this->payoutBucketClient = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $this->thaiHelper->getRegion(),
            'credentials' => [
                'key' => $this->thaiHelper->getAccessKey(),
                'secret' => $this->thaiHelper->getSecretKey()
            ]
        ]);
    }

    /**
     * @return mixed
     */
    public function getTcsPercentage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::TCS_PERCENTAGE, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getDeliveryFeeTaxPercentage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::DELIVERY_FEE_TAX_PERCENTAGE, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getCgstPercentage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::CGST_PERCENTAGE, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getSgstPercentage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::SGST_PERCENTAGE, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getIgstPercentage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::IGST_PERCENTAGE, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getNodalAccountNumber()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::NODAL_ACCOUNT_NUMBER, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getNodalAccountName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::NODAL_ACCOUNT_NAME, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getNodalAccountIfsc()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::NODAL_ACCOUNT_IFSC, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getCreditAccountNumber()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::CREDIT_ACCOUNT_NUMBER, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getCreditAccountName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::CREDIT_ACCOUNT_NAME, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getCreditAccountIfsc()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::CREDIT_ACCOUNT_IFSC, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getCreditAccountEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::CREDIT_ACCOUNT_EMAIL, $storeScope);
        return $percentage;
    }

    /**
     * @return mixed
     */
    public function getCreditAccountMobile()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::CREDIT_ACCOUNT_MOBILE, $storeScope);
        return $percentage;
    }

    public function getTCSAmount($orderBaseAmount, $deliveryFeeInclTax)
    {
        $deliveryFeeExclTax = $this->getDeliveryFeeExclTax($deliveryFeeInclTax);
        $percentage = $this->getTcsPercentage();
        $tcsBaseAmount = $orderBaseAmount + $deliveryFeeExclTax;
        $tcsAmount = $tcsBaseAmount * ($percentage/100);
        return $tcsAmount;
    }

    public function getDeliveryFeeExclTax($deliveryFeeInclTax)
    {
        $percentage = $this->getDeliveryFeeTaxPercentage();
        $divider = 100 + $percentage;
        $deliveryFeeExlTax =  ($deliveryFeeInclTax * 100) / $divider;
        return $deliveryFeeExlTax;
    }

    public function getBakewayCommission($actualCommission, $sellerId)
    {
        $locationData = $this->partnerLocation->getCollection()
                ->addFieldToFilter('seller_id', ['eq' => $sellerId])->getFirstItem();
        $cityData = $this->cities->getCollection()->addFieldToFilter('id', ['eq' => $locationData->getCityId()])->getFirstItem();
        $cityName = $cityData->getName();
        $result = [];
        
        if (in_array(strtolower($cityName), self::BAKEWAY_OPERATIONAL_CITIES)) {
            $cgstPercentage = $this->getCgstPercentage();
            $sgstPercentage = $this->getSgstPercentage();
            $cgstOnCommission = $actualCommission * ($cgstPercentage / 100);
            $sgstOnCommission = $actualCommission * ($sgstPercentage / 100);
            $bakeWayCommissionInclTax = $actualCommission + $cgstOnCommission + $sgstOnCommission;
            $result['commission_tax_details'] = [
                ['label' => 'CGST', ['percentage'=>$cgstPercentage, 'value' => $cgstOnCommission]],
                ['label' => 'SGST', ['percentage'=>$sgstPercentage, 'value' => $sgstOnCommission]]
            ];
        } else {
            $igstPercentage = $this->getIgstPercentage();
            $igstOnCommision = $actualCommission * ($igstPercentage / 100);
            $bakeWayCommissionInclTax = $actualCommission + $igstOnCommision;
            $result['commission_tax_details'] = [
                ['label' => 'IGST', ['percentage'=>$igstPercentage, 'value' => $igstOnCommision]]
            ];
        }
        $result['bakeway_commission'] = $bakeWayCommissionInclTax;
        return $result;
    }

    public function getSellerState($sellerId)
    {
        /**
         * TODO : For now keeping it static to Maharashtra. Below code needs to remove.
         */
        return 'Maharashtra';
    }

    public function getOrderPaymentGatewayPercentage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::ORDER_PG_PERCENTAGE, $storeScope);
        return $percentage;
    }

    /**
     * Get vendor amount adding shipping and tax
     * Deducting TCS and payment gateway fees.
     */
    public function getVendorAmountInclAll($orderId, $actualVendorAmount)
    {
        $vendorAmount = $actualVendorAmount;
        $collection = $this->objectManager->create(
            'Webkul\Marketplace\Model\Orders')
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection as $order) {
            $vendorAmount = $vendorAmount - $order->getTcsAmount();
            $vendorAmount = $vendorAmount - $order->getPaymentGatewayFee();
            $shippingCharge = $order->getShippingCharges();
            /*
             * check delivery flag
             */
            $deliveryFlag = $order['grab_delivery_flag'];

            /**
             * delivery fees set 0 when grab is avalivable for seller
             */
            if(!empty($deliveryFlag))
            {
                $shippingCharge = 0;
            }

            $vendorAmount = $vendorAmount + $shippingCharge;

        }
        $collection = $this->objectManager->create(
            'Webkul\Marketplace\Model\Saleslist')
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection as $order) {
            $vendorAmount = $vendorAmount + $order->getTotalTax();
        }
        return $vendorAmount;
    }

    public function getTcsByOrderId($orderId)
    {
        $tcsAmount = 0;
        $collection = $this->objectManager->create(
            'Webkul\Marketplace\Model\Orders')
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection as $order) {
            $tcsAmount =$order->getTcsAmount();
        }

        return $tcsAmount;
    }

    public function getPgFeeByOrderId($orderId)
    {
        $pgFee = 0;
        $collection = $this->objectManager->create(
            'Webkul\Marketplace\Model\Orders')
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection as $order) {
            $pgFee =$order->getPaymentGatewayFee();
        }

        return $pgFee;
    }

    public function getDeliveryChargeByOrderId($orderId)
    {
        $deliveryFee = 0;
        $collection = $this->objectManager->create(
            'Webkul\Marketplace\Model\Orders')
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection as $order) {
            $deliveryFee =$order->getDeliveryFee();
        }

        return $deliveryFee;
    }

    /**
     * @param $sellerId
     * @return bool
     */
    public function isTaxPaidByBakeway($sellerId)
    {
        $collection = $this->objectManager->create(
            'Webkul\Marketplace\Model\Seller')
            ->getCollection()
            ->addFieldToFilter('seller_id', ['eq' => $sellerId]);
        foreach ($collection as $seller) {
            $gstin = $seller->getData('userdata_gstin_number');
            if (trim($gstin) == '' || $gstin == null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return a list of unpaid orders.
     * @return mixed
     */
    public function getUnpaidOrderCollection() {
        $currentDateTime = new \DateTime('now', new \DateTimezone("Asia/Kolkata"));
        $currentDateString = $currentDateTime->format('Y-m-d H:i:s');
        $twoDaysAgo =  date('Y-m-d H:i:s', strtotime('-2 days', strtotime($currentDateString)));
       // $twoDaysAgo = $currentDateString;
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect(['customer_email','customer_firstname','customer_lastname','customer_is_guest','created_at', 'updated_at' ,'grab_delivery_flag']);
            //->removeFieldFromSelect('entity_id');
        $orderCollection->addFieldToFilter('main_table.updated_at', ['lteq' => $twoDaysAgo]);
       
        /*
         * added this code for exporting old days unpaid orders @bkwyadmin-1049
         */
        //$orderCollection->addFieldToFilter('main_table.updated_at', ['gteq' => '2018-08-02']);
        //$orderCollection->addFieldToFilter('main_table.updated_at', ['lteq' => '2018-08-20']);

        $orderCollection->getSelect()->joinInner(['mp_sl'=>$orderCollection->getTable('marketplace_saleslist')],
            'main_table.entity_id=mp_sl.order_id',
            ['entity_id','seller_id','order_id','paid_status','cpprostatus','total_amount',
                'actual_seller_amount','total_commission','magerealorder_id','commission_rate',
                'commission_tax_details','commission_incl_tax','is_shipping','total_tax','trans_id'
            ]);
        $orderCollection->getSelect()->joinInner(['mp_order'=>$orderCollection->getTable('marketplace_orders')],
            'main_table.entity_id=mp_order.order_id',
            ['payment_gateway_fee','delivery_fee','tcs_amount','convenience_fee_incl_tax','coupon_amount','tax_paid_by_bakeway']);
        $orderCollection->getSelect()->joinLeft(['mp_seller'=>$orderCollection->getTable('marketplace_userdata')],
            'mp_sl.seller_id=mp_seller.seller_id',
            ['store_owner_bank_account_number','store_owner_bank_ifsc'
                ,'store_owner_bank_registered_name','store_owner_mobile_no','store_owner_email']);
       
       // $orderCollection->getSelect()->joinLeft(['sosh'=>$orderCollection->getTable('sales_order_status_history')],
       //'sosh.parent_id = mp_order.order_id',
       //['created_at']);
        $orderCollection->getSelect()->where('mp_sl.paid_status=?', 0);
        $orderCollection->getSelect()->where('mp_sl.cpprostatus=?', 1);
        $orderCollection->getSelect()->where('mp_sl.trans_id=?', 0);
        $orderCollection->getSelect()->where('mp_sl.parent_item_id IS NULL');
        //$orderCollection->getSelect()->where('sosh.status=?',"complete");
        // $orderCollection->getSelect()->where('sosh.created_at >= "2018-08-02"');
         $orderCollection->getSelect()->group("mp_sl.entity_id");
         //echo  $orderCollection->getSelect();die;
        return $orderCollection;
    }

    /**
     * @param $length
     * @param string $charset
     * @return string
     */
    public function randString(
        $length,
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    ) {
        $str = 'tr-';
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count - 1)];
        }

        return $str;
    }

    /**
     * @return string
     */
    public function checkSellerTransId()
    {
        $uniqueId = $this->randString(11);
        $collection = $this->objectManager->create(
            'Bakeway\PayoutsCalculation\Model\Bakewaytransaction'
        )
            ->getCollection()
            ->addFieldToFilter('transaction_id', $uniqueId);
        $i = 0;
        foreach ($collection as $value) {
            ++$i;
        }
        if ($i != 0) {
            $this->checkBakewayTransId();
        } else {
            return $uniqueId;
        }
    }

    /**
     * @return string
     */
    public function checkBakewayTransId()
    {
        $uniqueId = $this->randString(11);
        $collection = $this->objectManager->create(
            'Bakeway\PayoutsCalculation\Model\Bakewaytransaction'
        )
            ->getCollection()
            ->addFieldToFilter('transaction_id', $uniqueId);
        $i = 0;
        foreach ($collection as $value) {
            ++$i;
        }
        if ($i != 0) {
            $this->checkBakewayTransId();
        } else {
            return $uniqueId;
        }
    }

    /**
     * @param array $rowData
     * @return bool|string
     */
    public function generateOutwordExcelSheet($rowData) {
        if (is_array($rowData)) {
            $transactionsArray = [];
            try {
                $i=1;
                $date = $this->date->gmtDate('d-m-Y');
                $dateToInsert = $this->date->gmtDate('d/m/Y');
                foreach ($rowData as $sellerId=>$row) {
                    $isSeller = $this->checkForSeller($sellerId);
                    if ($isSeller === true) {
                        $sellerTransId = $this->checkSellerTransId();
                        $bakewayTransId = $this->checkBakewayTransId();

                        /**
                         * adding seller amount in sheet
                         */
                        $this->excelParser->setActiveSheetIndex(0)
                            ->setCellValueExplicit('A' . $i, self::MODE_NEFT)
                            ->setCellValueExplicit('B' . $i, $sellerTransId)
                            ->setCellValueExplicit('C' . $i, $row['seller_account_name'])
                            ->setCellValueExplicit('D' . $i, $sellerId)
                            ->setCellValueExplicit('E' . $i, $row['pending_amount'])
                            ->setCellValueExplicit('F' . $i, $dateToInsert)
                            ->setCellValueExplicit('G' . $i, $row['seller_account_number'], \PHPExcel_Cell_DataType::TYPE_STRING)
                            ->setCellValueExplicit('H' . $i, $row['seller_account_ifsc'], \PHPExcel_Cell_DataType::TYPE_STRING)
                            ->setCellValueExplicit('I' . $i, $row['nodal_account_number'], \PHPExcel_Cell_DataType::TYPE_STRING)
                            ->setCellValueExplicit('J' . $i, $row['seller_email'])
                            ->setCellValueExplicit('K' . $i, $row['seller_mobile_number'], \PHPExcel_Cell_DataType::TYPE_STRING);
                        $i++;
                        /**
                         * Adding bakeway amount in sheet
                         */
                        $this->excelParser->setActiveSheetIndex(0)
                            ->setCellValueExplicit('A' . $i, self::MODE_NEFT)
                            ->setCellValueExplicit('B' . $i, $bakewayTransId)
                            ->setCellValueExplicit('C' . $i, $row['credit_account_name'])
                            ->setCellValueExplicit('D' . $i, self::BAKWAY_CODE)
                            ->setCellValueExplicit('E' . $i, $row['bakeway_amount'])
                            ->setCellValueExplicit('F' . $i, $dateToInsert)
                            ->setCellValueExplicit('G' . $i, $row['credit_account_number'], \PHPExcel_Cell_DataType::TYPE_STRING)
                            ->setCellValueExplicit('H' . $i, $row['credit_account_ifsc'], \PHPExcel_Cell_DataType::TYPE_STRING)
                            ->setCellValueExplicit('I' . $i, $row['nodal_account_number'], \PHPExcel_Cell_DataType::TYPE_STRING)
                            ->setCellValueExplicit('J' . $i, $this->getCreditAccountEmail())
                            ->setCellValueExplicit('K' . $i, $this->getCreditAccountMobile(), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $i++;
                        /**
                         * Adding Data to transactions array
                         */
                        $transactionsArray[$sellerId]['seller_trans_id'] = $sellerTransId;
                        $transactionsArray[$sellerId]['bakeway_trans_id'] = $bakewayTransId;
                        $transactionsArray[$sellerId]['seller_amount'] = $row['pending_amount'];
                        $transactionsArray[$sellerId]['bakeway_amount'] = $row['bakeway_amount'];
                        $transactionsArray[$sellerId]['sales_list_ids'] = $row['sales_list_ids'];
                    }
                }


                if (empty($transactionsArray)) {
                    return false;
                }
                $fileName = $date.'_'.time().'.xls';
                $fileObj = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA)->create('/outword/'.$date.'/');
                $path = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA)
                    ->getAbsolutePath('/outword/'.$date.'/'.$fileName) ;
                $excelWriter = new \PHPExcel_Writer_Excel2007($this->excelParser);
                $excelWriter->save($path);
                $this->saveFileToBucket('outword/'.$date.'/'.$fileName);
                /**
                 * updating database tables after excelsheet generated
                 */
                $this->updatePayoutsTransactions($transactionsArray, $fileName);

                return $fileName;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param array $transactions
     * @param string $fileName
     * @return void
     */
    public function updatePayoutsTransactions($transactions, $fileName) {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/payoutlog.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        if (is_array($transactions)) {
            foreach ($transactions as $sellerId=>$transaction) {
                /**
                 * adding record to seller_transaction
                 */
                if (isset($transaction['seller_trans_id']) && $transaction['seller_trans_id'] != '') {
                    $sellerTrans = $this->objectManager->create(
                        'Webkul\Marketplace\Model\Sellertransaction'
                    )->getCollection()
                        ->addFieldToFilter('transaction_id', $transaction['seller_trans_id']);
                    if (count($sellerTrans)) {
                        $id = 0;
                        foreach ($sellerTrans as $value) {
                            $id = $value->getId();
                        }
                        if ($id) {
                            $this->objectManager->create(
                                'Webkul\Marketplace\Model\Sellertransaction'
                            )->load($id)->delete();
                        }
                    }
                    $sellerTrans = $this->objectManager->create(
                        'Webkul\Marketplace\Model\Sellertransaction'
                    );
                    $sellerTrans->setTransactionId($transaction['seller_trans_id']);
                    $sellerTrans->setTransactionAmount($transaction['seller_amount']);
                    $sellerTrans->setType('Manual');
                    $sellerTrans->setMethod('Manual');
                    $sellerTrans->setSellerId($sellerId);
                    $sellerTrans->setCustomNote(Null);
                    $sellerTrans->setCreatedAt($this->date->gmtDate());
                    $sellerTrans->setUpdatedAt($this->date->gmtDate());
                    $sellerTrans->setSellerPendingNotification(1);
                    $sellerTrans->setTransactionStatus(self::TRANS_STATUS_PROCESSING);
                    $sellerTrans->setTransactionFile($fileName);
                    $sellerTrans->setTransactionInvoiceNumber($this->getTransactionInvoiceNo());
                    $sellerTrans = $sellerTrans->save();
                    $sellerTransEntityId = $sellerTrans->getId();
                    $transactionNumber = $sellerTrans->getTransactionId();
                }

                /**
                 * adding record to bakeway_payouts_transaction
                 */
                if (isset($transaction['bakeway_trans_id']) && $transaction['bakeway_trans_id'] != '') {
                    $bakewayTrans = $this->objectManager->create(
                        'Bakeway\PayoutsCalculation\Model\Bakewaytransaction'
                    )->getCollection()
                        ->addFieldToFilter('transaction_id', $transaction['bakeway_trans_id']);
                    if (count($bakewayTrans)) {
                        $id = 0;
                        foreach ($bakewayTrans as $value) {
                            $id = $value->getId();
                        }
                        if ($id) {
                            $this->objectManager->create(
                                'Bakeway\PayoutsCalculation\Model\Bakewaytransaction'
                            )->load($id)->delete();
                        }
                    }
                    $bakewayTrans = $this->objectManager->create(
                        'Bakeway\PayoutsCalculation\Model\Bakewaytransaction'
                    );
                    $bakewayTrans->setTransactionId($transaction['bakeway_trans_id']);
                    $bakewayTrans->setSellerTransactionId($transaction['seller_trans_id']);
                    $bakewayTrans->setTransactionAmount($transaction['bakeway_amount']);
                    $bakewayTrans->setCreatedAt($this->date->gmtDate());
                    $bakewayTrans->setUpdatedAt($this->date->gmtDate());
                    $bakewayTrans->setTransactionStatus(self::TRANS_STATUS_PROCESSING);
                    $bakewayTrans->setTransactionFileName($fileName);
                    $bakewayTrans = $bakewayTrans->save();
                    $bakewayTransEntityId = $bakewayTrans->getId();
                    $transactionNumber = $bakewayTrans->getTransactionId();
                }

                /**
                 * update table marketplace_saleslist with seller transaction id
                 */
                $saleslistids = $transaction['sales_list_ids'];
                $collection = $this->objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                )->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $saleslistids])
                    ->addFieldToFilter('order_id', ['neq' => 0])
                    ->addFieldToFilter('paid_status', 0)
                    ->addFieldToFilter('cpprostatus', ['neq' => 0]);
                foreach ($collection as $collectionData) {
                $logger->info("tran-file-id ".$sellerTransEntityId);
                    $collection->setSalesListData(
                        $collectionData->getId(),
                        ['trans_id' => $sellerTransEntityId]
                    );
                }
            }
        }
        return;
    }

    public function checkForSeller($sellerId) {
        try {
            $this->customerRepository->getById($sellerId);
            $sellerColl = $this->marketplaceHelper->getSellerDataBySellerId($sellerId);
            if ($sellerColl->count() > 0) {
                $seller = $sellerColl->getFirstItem();
                if ($seller->getData('is_seller') == 1) {
                    return true;
                } else {
                    return false;
                }
            }
            return false;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    public function saveFileToBucket($filename)
    {
        $file = $this->mediaHelper->collectFileInfo($this->thaiBucketStorage->getMediaBaseDirectory(), $filename);

        try {
            $this->payoutBucketClient->putObject([
                'ACL' => 'public-read',
                'Body' => $file['content'],
                'Bucket' => self::OUTWORD_BUCKET_NAME,
                'ContentType' => \GuzzleHttp\Psr7\mimetype_from_filename($file['filename']),
                'Key' => $filename
            ]);
        } catch (\Exception $e) {
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOutwordBucketUrl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $percentage = $this->scopeConfig->getValue(self::OUTWORD_S3_URL, $storeScope);
        return $percentage;
    }

    /**
     * @param $transId
     * @param $sellerId
     */
    public function updateSellerStatistics($transId, $sellerId) {
        try {
            $actparterprocost = 0;
            $totalamount = 0;
            $taxPaidByBakeway = 0;
            $helper = $this->objectManager->get('Webkul\Marketplace\Helper\Data');
            $taxToSeller = $helper->getConfigTaxManage();

            $orderinfo = '';

            $collection = $this->objectManager->create(
                'Webkul\Marketplace\Model\Saleslist'
            )->getCollection()
                ->addFieldToFilter('trans_id', $transId)
                ->addFieldToFilter('order_id', ['neq' => 0])
                ->addFieldToFilter('paid_status', 0)
                ->addFieldToFilter('cpprostatus', ['neq' => 0]);
            foreach ($collection as $row) {
                $sellerId = $row->getSellerId();
                $order = $this->salesOrderRepository->get($row['order_id']);
                $taxAmount = $row['total_tax'];
                $marketplaceOrders = $this->objectManager->create(
                    'Webkul\Marketplace\Model\Orders'
                )->getCollection()
                    ->addFieldToFilter('order_id', $row['order_id'])
                    ->addFieldToFilter('seller_id', $sellerId);
                foreach ($marketplaceOrders as $tracking) {
                    $taxToSeller = $tracking['tax_to_seller'];
                    /**
                     * checking if tax paid by bakeway
                     */
                    $taxPaidByBakeway = $tracking['tax_paid_by_bakeway'];
                }
                $vendorTaxAmount = 0;
                if ($taxToSeller) {
                    $vendorTaxAmount = $taxAmount;
                }
                /**
                 * Bakeway Fields
                 */
                if ($taxPaidByBakeway == 1) {
                    $vendorTaxAmount = 0;
                }
                $codCharges = 0;
                $shippingCharges = 0;
                /**
                 * Bakeway Fields
                 */
                $bakewayFees = 0;
                if (!empty($row['cod_charges'])) {
                    $codCharges = $row->getCodCharges();
                }
                if ($row->getIsShipping() == 1) {
                    foreach ($marketplaceOrders as $tracking) {
                        $shippingamount = $tracking->getShippingCharges();
                        /**
                         * If Tax paid by bakeway applying shipping excluding tax
                         */
                        if ($taxPaidByBakeway == 1) {
                            $shippingamount = $this->getDeliveryFeeExclTax($shippingamount);
                        }
                        $refundedShippingAmount = $tracking->getRefundedShippingCharges();
                        $shippingCharges = $shippingamount - $refundedShippingAmount;
                        /**
                         * Bakeway Fields
                         */
                        $bakewayFees = $bakewayFees + $tracking->getTcsAmount();
                        $bakewayFees = $bakewayFees + $tracking->getPaymentGatewayFee();
                    }
                }
                $actparterprocost = $actparterprocost +
                    $row->getActualSellerAmount() +
                    $vendorTaxAmount +
                    $codCharges +
                    $shippingCharges;
                /**
                 *Bakeway Calculation Deducting the TCS and PG Fee
                 */
                $actparterprocost = $actparterprocost - $bakewayFees;

                $totalamount = $totalamount +
                    $row->getTotalAmount() +
                    $taxAmount +
                    $codCharges +
                    $shippingCharges;

                $collection->setSalesListData(
                    $row->getId(),
                    ['paid_status' => 1]
                );
            }
            if ($actparterprocost) {
                $collectionverifyread = $this->objectManager->create(
                    'Webkul\Marketplace\Model\Saleperpartner'
                )->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId);
                if (count($collectionverifyread) >= 1) {
                    $id = 0;
                    $totalremain = 0;
                    $amountpaid = 0;
                    foreach ($collectionverifyread as $verifyrow) {
                        $id = $verifyrow->getId();
                        if ($verifyrow->getAmountRemain() >= $actparterprocost) {
                            $totalremain = $verifyrow->getAmountRemain() - $actparterprocost;
                        }
                        $amountpaid = $verifyrow->getAmountReceived();
                    }
                    $verifyrow = $this->objectManager->create(
                        'Webkul\Marketplace\Model\Saleperpartner'
                    )->load($id);
                    $totalrecived = $actparterprocost + $amountpaid;
                    $verifyrow->setLastAmountPaid($actparterprocost);
                    $verifyrow->setAmountReceived($totalrecived);
                    $verifyrow->setAmountRemain($totalremain);
                    $verifyrow->setUpdatedAt($this->date->gmtDate());
                    $verifyrow->save();
                } else {
                    $percent = $helper->getConfigCommissionRate();
                    $collectionf = $this->objectManager->create(
                        'Webkul\Marketplace\Model\Saleperpartner'
                    );
                    $collectionf->setSellerId($sellerId);
                    $collectionf->setTotalSale($totalamount);
                    $collectionf->setLastAmountPaid($actparterprocost);
                    $collectionf->setAmountReceived($actparterprocost);
                    $collectionf->setAmountRemain(0);
                    $collectionf->setCommissionRate($percent);
                    $collectionf->setTotalCommission($totalamount - $actparterprocost);
                    $collectionf->setCreatedAt($this->date->gmtDate());
                    $collectionf->setUpdatedAt($this->date->gmtDate());
                    $collectionf->save();
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t complete transaction right now. %1', $e->getMessage()));
        }
    }

    /**
     * return last increment id
     * @return mixed
     */
    public function getLastInsertedId()
    {
        $sellerTrans = $this->objectManager->create('Webkul\Marketplace\Model\Sellertransaction')->getCollection()
            ->getLastItem();
        if(!empty($sellerTrans->getData('entity_id')))
        {
            return $sellerTrans->getData('entity_id');
        }else
        {
            return;
        }
    }

    /**
     * return transcation number
     */
    public function getTransactionInvoiceNo()
    {
        $pst = date('m');
        if($pst >= 4) {
            $currentYear =date('Y');
            $financialYearFrom = $currentYear;
            $toYear = date('Y', strtotime('+1 year'));
            $financialYearTo =$toYear;
            $flag = true;
        }

        else {
            $currentYear =date('Y', strtotime('-1 year'));
            $financialYearFrom = $currentYear;
            $toYear = date('Y');
            $financialYearTo = $toYear;
            $flag = false;
        }

        if($flag === true){
            $fromDate = date("01-04-".$financialYearFrom);
            $toDate = date("31-03-".$financialYearTo);

        }else{
            $fromDate = date("01-04-".$financialYearFrom);
            $toDate = date("31-03-".$financialYearTo);
        }

        $fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
        $toDate = date('Y-m-d H:i:s', strtotime($toDate));

        $sellerTrans = $this->objectManager->create('Webkul\Marketplace\Model\Sellertransaction')->getCollection()
            ->addFieldToFilter("created_at",['from'=>$fromDate, 'to'=>$toDate]);


        $lastInsertedId =  count($sellerTrans);

        if($lastInsertedId < 1){

            $lastInsertedNum= 1;
        }else{
            $lastInsertedNum = $lastInsertedId++;
        }

        return $lastInsertedNum;


    }
}