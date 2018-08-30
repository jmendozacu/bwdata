<?php
/* 
 * Tab class for Hubassignments
 * @vendor Dischem
 * @module Dischem_Hubassignments
 * @created at 13-01-2016 11:22 PM IST
 * @modified at 13-01-2016 11:40 PM IST 
 * @author Srinihi D<srinidhi.damle@zensar.in>
 */
namespace Bakeway\Partnerlocations\Block\Adminhtml\Locations\Edit;

/**
 * left menu on the add new Page
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct() {
        parent::_construct();
        $this->setId('grid_record');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Edit Store Addresses'));
    }
}