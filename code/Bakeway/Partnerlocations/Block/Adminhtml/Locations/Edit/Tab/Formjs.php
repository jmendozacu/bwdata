<?php
/* 
 
 */
namespace Bakeway\Partnerlocations\Block\Adminhtml\Locations\Edit\Tab;

use Bakeway\Cities\Helper\Data as BakewayCitiesHelper;
use Bakeway\Partnerlocations\Helper\Data as Partnerlocationshelper;
class Formjs extends \Magento\Backend\Block\Widget\Form\Generic
implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'tab/formjs.phtml';
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
 
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    //protected $_wysiwygConfig;
    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $_auth;
    
    protected $_cmsPage;
    /**
     * @var BakewayCitiesHelper
     */
    protected $bakewayCitiesHelper;

    /**
     * @var Partnerlocationshelper
     */
    protected $partnerlocationshelper;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Cms\Model\Page $cmsPage
     * @param \Magento\Backend\Model\Auth $auth
     * @param BakewayCitiesHelper $bakewayCitiesHelper
     * @param Partnerlocationshelper $partnerlocationshelper
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Cms\Model\Page $cmsPage,
        \Magento\Backend\Model\Auth $auth,
        BakewayCitiesHelper $bakewayCitiesHelper,
        Partnerlocationshelper $partnerlocationshelper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_cmsPage = $cmsPage;
        $this->_auth = $auth;
        $this->bakewayCitiesHelper = $bakewayCitiesHelper;
        $this->partnerlocationshelper = $partnerlocationshelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    

    
    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Store Addresses1');
    }
 
    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Store Addresses1');
    }
 
    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }
 
    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
 
    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }    
}