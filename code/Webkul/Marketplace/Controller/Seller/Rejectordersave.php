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

namespace Webkul\Marketplace\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Bakeway\Vendorapi\Model\Resource\VendorOrderRepository as VendorOrderRepository;

/**
 * Marketplace Seller List controller.
 */
class Rejectordersave extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var VendorOrderRepository
     */
    protected $vendorOrderRepository;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        VendorOrderRepository $vendorOrderRepository
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->vendorOrderRepository = $vendorOrderRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $request = $this->getRequest()->getParams();

        if(isset($request['order_id']) && isset($request['seller_id']) &&  isset($request['reject-order-reason']))
        {
           $rejectReason = $request['reject-order-reason'];

            if($request['reject-order-reason'] == "other-reason"){
                if(isset($request['reject-reason-other'])){
                    $rejectReason = $request['reject-reason-other'];
                }else{
                    $rejectReason = "";
                }

                }
            $this->vendorOrderRepository->vendorrejectOrder($request['seller_id'],$request['order_id'],$rejectReason);
            $this->_redirect('partner/order/view/id/'.$request['order_id']);

        }


    }
}
