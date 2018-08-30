<?php

namespace Bakeway\Reports\Controller\Adminhtml\Customer;

class ExportCsv extends \Magento\Backend\App\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /** @var OrderFactory */
    protected $orderFactory;

    /** @var EmailItems */
    protected $emailItems;

    /** @var OrderStatushistory */
    protected $orderStatushistory;

    /** @var TimezoneInterface */
    protected $timezoneInterface;

    /** @var \Magento\Framework\Controller\Result\RawFactory */
    protected $resultRawFactory;

    /** @var \Magento\Framework\File\Csv */
    protected $csvWriter;

    /** @var \Magento\Framework\App\Response\Http\FileFactory */
    protected $fileFactory;

    /** @var \Magento\Framework\App\Filesystem\DirectoryList */
    protected $directoryList;

    /**
     * Construct
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Bakeway\OrderstatusEmail\Block\Order\Email\Items $emailItems
     * @param \Magento\Sales\Model\Order\Status\History $orderStatushistory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\File\Csv $csvWriter
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     */
    public function __construct(
    \Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            \Magento\Sales\Model\OrderFactory $orderFactory,
            \Bakeway\OrderstatusEmail\Block\Order\Email\Items $emailItems,
            \Magento\Sales\Model\Order\Status\History $orderStatushistory,
            \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
            \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
            \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
            \Magento\Framework\File\Csv $csvWriter,
            \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->orderFactory = $orderFactory;
        $this->emailItems = $emailItems;
        $this->orderStatushistory = $orderStatushistory;
        $this->timezoneInterface = $timezoneInterface;

        $this->csvWriter = $csvWriter;
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
    }

    /**
     * Export order data
     * @return string
     */
    public function execute()
    {
        $csvRow[0] = $csvRow[1] = $cakeMsg = $cakeFlavour = $cakeIngre = $cakeWeight = $bakeryDetails['street_address'] = "";
        $orderData['comment'] = "";
        $this->resultPage = $this->resultPageFactory->create();
        $collection = $this->resultPage->getLayout()->createBlock('Bakeway\Reports\Block\Adminhtml\Orders\Grid')->getCsvFile();
        $csvfile = $collection['value'];

        $targetDir = BP . "/var/";
        $f = fopen('php://memory', 'w');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $csvRows = $this->getdata($targetDir . $csvfile);
        array_shift($csvRows);
        $data = array();
        if (!empty($csvRows)) {
            $data1 = array(
                'Order ID',
                'Delivery Type',
                'Order Date & Time',
                'Seller Id',
                'Bakery Name',
                'Time to accept/ Reject',
                'Delay to Mark as Completed',
                'Reject Reason',
                'Store Owner Mobile No.',
                'Store Manager Mobile No.',
                'Note to bakery',
                'Sender Name',
                'Sender Email',
                'Sender Address',
                'Delivery Date & Time',
                'Delivery Person Name',
                'Delivery Address',
                'Order Value',
                'Shipping & Handling Charges',
                'Payment Mode',
                'Offer Applied Coupon',
                'Bakery Address',
                'Product Name',
                'Product Sku',
                'Product Price',
                'Product Image',
                'Product Flavour',
                'Weight of Product',
                'Ingredients',
                'Sender Phone number',
                'Delivery Person number',
                'Message on the cake',
                'Commission',
                'Convenience Fee',
                'Payment Gateway Charge',
                'Discount Amount',
                'GST Amount',
                'City Name',
                'Order Status',
                'Actual Seller Amount',
                'Paid Status',
                'Completed At'
            );
            array_push($data, $data1);
            foreach ($csvRows as $csvRow) {
                $bakeryDetails = [];
                if (isset($csvRow[0])) {
                    $commission = $csvRow[21];
                    $convenienceFee = $csvRow[22];
                    $pgCharge = $csvRow[23];
                    $discountAmount = $csvRow[24];
                    $gstAmount = $csvRow[25];
                    $cityName = $csvRow[26];
                    $orderStatus = $csvRow[27];
                    $actualSellerAmount = $csvRow[28];
                    $paidStatus = $csvRow[29];
                    $completedAt = $csvRow[30];

                    $orderData = $this->orderFactory->create()->loadByIncrementId($csvRow[0]);
                    $customMessage = [];
                    $orderItemData = $orderData->getAllVisibleItems();
                    foreach ($orderItemData as $orderItems) {
                        $customMessage[] = $orderItems->getData('extension_attributes')->getCustomMessage();
                    }
                    $customMessage[0] = 'jbhbjbh';
                    if (!empty($customMessage[0])):
                        $cakeMsg = $customMessage[0];
                    endif;

                    $_items = $orderData->getItemsCollection();
                    foreach ($_items as $_item) {
                        if ($_item->getParentItem()) {
                            continue;
                        }
                        try {
                            $getSku = $_item->getSku();
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                            $getSku = false;
                        }
                        if (!empty($getSku)) {

                            $cakeFlavour = $_item["item_flavour"];
                            $cakeIngre = $_item["item_ingredient"];
                            $cakeWeight = $_item["item_weight"];
                            $imageUrl = $_item['item_image_url'];
                            $bakeryDetails = $this->emailItems->getSellerAddressDetails($_item);
                            if (isset($bakeryDetails['street_address'])) {
                                $bakeryAddress = $bakeryDetails['street_address'];
                            } else {
                                $bakeryAddress = "NA";
                            }
                            $csvRow[21] = $bakeryAddress;
                            $csvRow[22] = $_item->getName();
                            $csvRow[23] = $_item->getSku();
                            if ($_item['base_price_incl_tax']) {
                                $price = $_item['base_price_incl_tax'];
                            } else {
                                $price = $_item['base_price'];
                            }
                            $csvRow[24] = $price;
                            $csvRow[25] = $imageUrl;
                            $csvRow[26] = $cakeFlavour;
                            $csvRow[27] = $cakeWeight;
                            $csvRow[28] = $cakeIngre;
                            $csvRow[29] = $orderData->getBillingAddress()->getTelephone();
                            $csvRow[30] = $orderData->getShippingAddress()->getTelephone();
                            $csvRow[31] = $cakeMsg;
                            $csvRow[32] = $commission;
                            $csvRow[33] = $convenienceFee;
                            $csvRow[34] = $pgCharge;
                            $csvRow[35] = $discountAmount;
                            $csvRow[36] = $gstAmount;
                            $csvRow[37] = $cityName;
                            $csvRow[38] = $orderStatus;
                            $csvRow[39] = $actualSellerAmount;
                            $csvRow[40] = $paidStatus;
                            $csvRow[41] = $completedAt;
                        }
                        array_push($data, $csvRow);
                    }
                }
            }
            $fileDirectory = \Magento\Framework\App\Filesystem\DirectoryList::MEDIA;
            $fileName = "orders-reports" . '.csv';
            $filePath = $this->directoryList->getPath($fileDirectory) . "/" . $fileName;

            $this->csvWriter
                    ->setEnclosure('"')
                    ->setDelimiter(',')
                    ->saveData($filePath, $data);

            $this->fileFactory->create(
                    $fileName,
                    [
                'type' => "filename",
                'value' => $fileName,
                'rm' => true,
                    ], \Magento\Framework\App\Filesystem\DirectoryList::MEDIA,
                    'text/csv', null
            );

            $resultRaw = $this->resultRawFactory->create();
            return $resultRaw;
        }
    }

    /**
     * @param $csvFile
     * @return array
     */
    function getdata($csvFile)
    {
        $file_handle = fopen($csvFile, 'r');
        while (!feof($file_handle)) {
            $line_of_text[] = fgetcsv($file_handle, 1024);
        }
        fclose($file_handle);
        return $line_of_text;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bakeway_Reports::report_custom_sales_orders');
    }

    /*
     * @param $incrementId
     * @param $todate
     */

    public function getAcceptRejectTime($incrementId,
            $orderdDate,
            $field = null)
    {


        $collection = $this->orderStatushistory->getCollection()
                ->addFieldToSelect(['status', 'created_at', 'comment'])
                ->addFieldToFilter(
                        'status',
                        ['in' => [\Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_ACCEPTED,
                        \Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_REJECTED]
                        ]
                )
                ->addFieldToFilter('entity_name', ['eq' => 'order'])
                ->addFieldToFilter('parent_id', ['eq' => $incrementId])
                ->getFirstItem();

        $fromDate = $this->timezoneInterface->date($collection['created_at'])->format('m/d/y H:i:s');
        $fromDate = new \DateTime($fromDate);
        $orderdDate = new \DateTime($orderdDate);

        $interval = $fromDate->diff($orderdDate);
        $timeDiff = $interval->format('%d days %h hours %I minutes ');


        if (isset($timeDiff)) {
            return $timeDiff;
        }
        return;
    }

    /*
     * @param $incrementId
     */

    public function getRejectReason($incrementId)
    {


        $collection = $this->orderStatushistory->getCollection()
                ->addFieldToSelect(['status', 'created_at', 'comment'])
                ->addFieldToFilter(
                        'status',
                        ['in' => [
                        \Bakeway\Vendorapi\Model\OrderStatus::STATUS_PARTNER_REJECTED]
                        ]
                )
                ->addFieldToFilter('entity_name', ['eq' => 'order'])
                ->addFieldToFilter('parent_id', ['eq' => $incrementId])
                ->getFirstItem();
        if (!empty($collection->getData('comment'))) {
            return $collection->getData('comment');
        }
        return;
    }

}
