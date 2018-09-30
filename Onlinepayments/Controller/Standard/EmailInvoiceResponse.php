<?php

namespace Urjakart\Onlinepayments\Controller\Standard;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ObjectManager;

class EmailInvoiceResponse extends Action {

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $pageFactory)
    {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $auth = $this->isAuthorized();
        $resultPage = $this->_pageFactory->create();
        if ($auth) {
            $params = $this->getRequest()->getParams();
            $objectManager = ObjectManager::getInstance();
            $orders = $objectManager->get('Magento\Sales\Model\Order');
            $paymentMethod = $objectManager->get('Urjakart\Onlinepayments\Model\OnlinePayment');
            $order = $orders->loadByIncrementId($params['productinfo']);
            $payment = $order->getPayment();
            if ($paymentMethod->validateResponse($params)) {
                $paymentMethod->postProcessing($order, $payment, $params);
            } else {
                $this->messageManager->addErrorMessage(__('Payment failed. Please try again or choose a different payment method'));
                $paymentMethod->postProcessing($order, $payment, $params);
            }
        }
        $resultPage->getLayout()->initMessages();
        $resultPage->getLayout()->getBlock('email_invoice_response')->setName($auth);
        $pageMainTitle = $resultPage->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle && $pageMainTitle instanceof \Magento\Theme\Block\Html\Title) {
            $pageMainTitle->setPageTitle(' ');
        }

        return $resultPage;
    }

    private function isAuthorized() {
        $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $isRefererProd = strpos($referer, 'secure.payu.in') ? true : false;
        $isRefererTest = strpos($referer, 'test.payu.in') ? true : false;

        return $isRefererProd || $isRefererTest;
    }
}