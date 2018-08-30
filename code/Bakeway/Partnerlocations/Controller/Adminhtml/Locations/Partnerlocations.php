<?php
namespace Bakeway\Partnerlocations\Controller\Adminhtml\Locations;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;


class Partnerlocations extends \Magento\Customer\Controller\Adminhtml\Index
{
    /**
     * Customer compare grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}
