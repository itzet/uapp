<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Checkout\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;

class SmsApi extends Template
{
    const MSG_API_URL = 'https://api.urjakart.com/sms.php';

    private $checkoutSession;

    private $orderRepository;

    private $_curl;

    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getSmsApiCall() {
        try {
            $om = ObjectManager::getInstance();
            $this->checkoutSession = $om->get('Magento\Checkout\Model\Session');
            $this->orderRepository = $om->get('Magento\Sales\Api\OrderRepositoryInterface');
            $this->_curl = $om->get('Magento\Framework\HTTP\Client\Curl');
            $order  = $this->orderRepository->get($this->checkoutSession->getLastOrderId());
            $payment = $order->getPayment();
            $method = $payment->getMethodInstance();
            $code = $method->getCode();
            $paymentCodes = ["creditcard", "debitcard", "emi", "netbanking", "payumoney"];
            if (!in_array($code, $paymentCodes)) {
                $orderId = $order->getRealOrderId();
                $grandTotal = $order->getGrandTotal();
                $firstName = $order->getShippingAddress()->getFirstname();
                $mobile = $order->getShippingAddress()->getTelephone();
                $msg = 'Confirmation%20of%20Order%20No%20' . $orderId . ':%20Dear%20' . $firstName . ',%20we%20have%20received%20your%20order%20no.%20' . $orderId . ',%20amounting%20to%20Rs.' . number_format($grandTotal, 0) . ',%20and%20it%20is%20being%20processed.';
                $url = self::MSG_API_URL;
                $url .= '?msg=' . $msg;
                $url .= '&rec=' . $mobile;
                $url .= '&from=URJKRT';
                $this->_curl->get($url);
            }
        } catch (\Exception $e) {
            // handel sms api exception
        }
    }
}