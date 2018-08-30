<?php
/* 
 * Form container for Hubassignments
 */
namespace Bakeway\Partnerlocations\Block\Adminhtml\Locations;

use Bakeway\Partnerlocations\Helper\Data as Partnerlocationhelper;
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /*
     * @var Partnerlocationhelper;
     */
     protected $_partnerlocationhelper;
 
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        Partnerlocationhelper $partnerlocationhelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_partnerlocationhelper = $partnerlocationhelper;
        parent::__construct($context, $data);
    }
    
    /**
     * Initialize blog post edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Bakeway_Partnerlocations';
        $this->_controller = 'adminhtml_locations';
 
        parent::_construct();


        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->update('delete', 'label', __('Delete'));
        /*
         * remove delete button when seller have only one store
         */
        $id  = $this->getRequest()->getParam('id');
        $seller_id = $this->_partnerlocationhelper->getSellerId($id);
        $this->removeButton('back');

        $storeAddresscount = $this->_partnerlocationhelper->getSellerStoreAddressCount($seller_id);
        if($storeAddresscount === 1):
        $this->removeButton('delete');
        endif;

    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'content');
                }
            };
        ";
        return parent::_prepareLayout();
    }


}