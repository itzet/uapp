<?php

namespace Urjakart\Sellerform\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ObjectManager;

class Index extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(PageFactory $resultPageFactory) {
        $objectManager = ObjectManager::getInstance();
        $context = $objectManager->get('Magento\Framework\App\Action\Context');
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(){
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $resultPage = $this->resultPageFactory->create();
        $pageMainTitle = $resultPage->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle && $pageMainTitle instanceof \Magento\Theme\Block\Html\Title) {
            $pageMainTitle->setPageTitle('Form Sell On Urjakart');
        }
        return $resultPage;
    }

}