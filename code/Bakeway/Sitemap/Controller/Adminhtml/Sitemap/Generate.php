<?php
namespace Bakeway\Sitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Bakeway\Sitemap\Model\Sitemap as SitemapModel;

class Generate extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;


    protected $sitemapModel;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SitemapModel $sitemapModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SitemapModel $sitemapModel
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->sitemapModel = $sitemapModel;
    }

    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();

        try{
            $this->sitemapModel->generateXml();

        }catch (Exception $e)
        {
            echo $e->getMessage();
        }

        $this->messageManager->addSuccess(__('Sitemap has been created.'));
        $this->_redirect('sitemap/sitemap/index');
        return;


    }
}
