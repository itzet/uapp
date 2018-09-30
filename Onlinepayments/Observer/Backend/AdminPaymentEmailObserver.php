<?php
namespace Urjakart\Onlinepayments\Observer\Backend;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\App\ObjectManager;

class AdminPaymentEmailObserver implements ObserverInterface {

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Modify or add new var data for order email template.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer) {
        $sender = $observer->getEvent()->getData('transport');
        $this->order = $sender->getData('order');
        $paymentCode = $this->order->getPayment()->getMethodInstance()->getCode();
        if ($paymentCode === 'quotation_quote') {
            $sender->setData('payment_html', 'Payment Received');
        }
        if ( $paymentCode === 'creditcard' || $paymentCode === 'payu' ) {
            $this->objectManager = ObjectManager::getInstance();
            $this->messageManager= $this->objectManager->get('Magento\Framework\Message\ManagerInterface');
            $emailInvoice = $this->emailInvoiceData();
            if (
                !empty($emailInvoice["Status"]) &&
                !empty($emailInvoice["URL"]) &&
                $emailInvoice["Status"] === 'Success'
            ) {
                $sender->setData('payment_html', $this->btnHtml($emailInvoice["URL"]));
            } else {
                $this->messageManager->addErrorMessage(__('Payment url not generated!'));
            }
        }
    }

    /**
     * Gather the payment config data and process the request
     * email invoice with payment url.
     *
     * @return array of email invoice
     */
    private function emailInvoiceData()
    {
        try {
            $paymentConfig = $this->objectManager->get('Urjakart\Onlinepayments\Model\OnlinePayment');
            $wsUrl = $paymentConfig->getInfoUrl();
            $key = $paymentConfig->getConfigData("merchant_key");
            $salt = $paymentConfig->getConfigData("salt");
            $command = "create_invoice";
            $var1 = $this->getVarData();
            $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
            $hash = strtolower(hash('sha512', $hash_str));
            $data = array('key' => $key, 'hash' => $hash, 'var1' => $var1, 'command' => $command);

            return $this->requestPaymentUrl($wsUrl, $data);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Invalid online payment configuration.'));
        }
    }

    /**
     * Gather the order data of product and create api
     * var1 json object for request.
     *
     * @return json string
     */
    private function getVarData() {

        try {
            $countryId = $this->order->getBillingAddress()->getCountryId();
            $country = $this->objectManager
                ->create('Magento\Directory\Model\Country')->load($countryId);
            $street = $this->order->getBillingAddress()->getStreet();
            $street = (!empty($street[0]) ? $street[0] : '') . ' ' . (!empty($street[1]) ? $street[1] : '');
            $var = [
                "amount" => round($this->order->getGrandTotal()),
                "txnid" => substr(hash('sha256', mt_rand() . microtime()), 0, 20),
                "productinfo" => $this->order->getRealOrderId(),
                "firstname" => $this->order->getCustomerFirstname(),
                "email" => $this->order->getCustomerEmail(),
                "phone" => $this->order->getBillingAddress()->getTelephone(),
                "address1" => $street,
                "city" => $this->order->getBillingAddress()->getCity(),
                "state" => $this->order->getBillingAddress()->getRegion(),
                "country" => $country->getName(),
                "zipcode" => $this->order->getBillingAddress()->getPostcode(),
                "validation_period" => 7,
                "send_email_now" => 1
            ];

            return json_encode($var);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('payment data preparation issue.'));
        }
    }

    /**
     * Execute curl request hit payu api and get the response
     * from payu
     *
     * @return array
     */
    private function requestPaymentUrl($wsUrl, $data)
    {
        $curl = $this->objectManager->get('Magento\Framework\HTTP\Client\Curl');
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
        $curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->post($wsUrl, $data);

        return json_decode($curl->getBody(),1);
    }

    /**
     * Create the payment button and related information.
     *
     * @return string
     */
    private function btnHtml($url)
    {
        $style = 'background-color: #4CAF50;';
        $style .= 'border: none;';
        $style .= 'color: white;';
        $style .= 'padding: 10px 30px;';
        $style .= 'text-align: center;';
        $style .= 'text-decoration: none;';
        $style .= 'display: inline-block;';
        $style .= 'font-size: 16px;';
        $style .= 'cursor: pointer;';
        $style .= '-moz-border-radius: 20px;';
        $style .= '-webkit-border-radius: 20px;';
        $style .= 'border-radius: 20px;';
        $style .= 'font-style: oblique;';
        $style .= 'font-weight: 900;';
        $style .= 'font-variant: small-caps;';
        $style .= 'box-shadow: 10px 10px 2px green;';
        $btn = '<p>Payment button valid for 7days.</p><br>';
        $btn .= '<a href="' . $url . '" style="'. $style .'">Pay Now</a>';

        return $btn;
    }
}