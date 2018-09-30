<?php

namespace Urjakart\Onlinepayments\Model;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Store\Model\ScopeInterface;

class OnlinePayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const ACC_BIZ = 'payubiz';
    const ACC_MONEY = 'payumoney';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'creditcard';

    /**
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    protected $_supportedCurrencyCodes = array(
        'AFN', 'ALL', 'DZD', 'ARS', 'AUD', 'AZN', 'BSD', 'BDT', 'BBD',
        'BZD', 'BMD', 'BOB', 'BWP', 'BRL', 'GBP', 'BND', 'BGN', 'CAD',
        'CLP', 'CNY', 'COP', 'CRC', 'HRK', 'CZK', 'DKK', 'DOP', 'XCD',
        'EGP', 'EUR', 'FJD', 'GTQ', 'HKD', 'HNL', 'HUF', 'INR', 'IDR',
        'ILS', 'JMD', 'JPY', 'KZT', 'KES', 'LAK', 'MMK', 'LBP', 'LRD',
        'MOP', 'MYR', 'MVR', 'MRO', 'MUR', 'MXN', 'MAD', 'NPR', 'TWD',
        'NZD', 'NIO', 'NOK', 'PKR', 'PGK', 'PEN', 'PHP', 'PLN', 'QAR',
        'RON', 'RUB', 'WST', 'SAR', 'SCR', 'SGF', 'SBD', 'ZAR', 'KRW',
        'LKR', 'SEK', 'CHF', 'SYP', 'THB', 'TOP', 'TTD', 'TRY', 'UAH',
        'AED', 'USD', 'VUV', 'VND', 'XOF', 'YER'
    );

    protected $checkoutSession;

    /**
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Urjakart\Onlinepayments\Helper\OnlinePaymentHelper $helper
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Urjakart\Onlinepayments\Helper\OnlinePaymentHelper $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
        $this->checkoutSession = $checkoutSession;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

    }

    public function canUseForCurrency($currencyCode) {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    public function getRedirectUrl() {
        return $this->helper->getUrl($this->getConfigData('redirect_url'));
    }

    public function getReturnUrl() {
        return $this->helper->getUrl($this->getConfigData('return_url'));
    }

    public function getCancelUrl() {
        return $this->helper->getUrl($this->getConfigData('cancel_url'));
    }

    public function getEmiUrl() {
        return $this->helper->getUrl($this->getConfigData('emi_url'));
    }

    public function getCardUrl() {
        return $this->helper->getUrl($this->getConfigData('card_url'));
    }

    /**
     * Return url according to environment
     * @return string
     */
    public function getCgiUrl() {
        $env = $this->getConfigData('environment');
        if ($env === 'production') {
            return $this->getConfigData('production_url');
        }
        return $this->getConfigData('sandbox_url');
    }

    public function getInfoUrl() {
        $env = $this->getConfigData('environment');
        $url = rtrim($this->getCgiUrl(), '_payment');
        $url .= 'merchant/postservice.php?form=2';
        if ($env === 'production') {
            return str_replace('secure', 'info', $url);
        }
        return $url;
    }

    public function getWalletList() {
        $wallets = [
            'payumoney' => [],
            'paytm' => []
        ];
        foreach ($wallets as $code => $val) {
            $active = 'payment/' . $code . '/active';
            $title = 'payment/' . $code . '/title';
            $min = 'payment/' . $code . '/min_order_total';
            $max = 'payment/' . $code . '/max_order_total';
            $wallets[$code]['active'] = $this->_scopeConfig->getValue($active, ScopeInterface::SCOPE_STORE, $this->getStore());
            $wallets[$code]['title'] = $this->_scopeConfig->getValue($title, ScopeInterface::SCOPE_STORE, $this->getStore());
            $wallets[$code]['min_order'] = (int)$this->_scopeConfig->getValue($min, ScopeInterface::SCOPE_STORE, $this->getStore());
            $wallets[$code]['max_order'] = (int)$this->_scopeConfig->getValue($max, ScopeInterface::SCOPE_STORE, $this->getStore());
        }

        return json_encode($wallets);
    }

    public function getEMIList() {
        $emis = [
            'emi' => [],
            'kisshtpay' => []
        ];
        foreach ($emis as $code => $val) {
            $active = 'payment/' . $code . '/active';
            $title = 'payment/' . $code . '/title';
            $min = 'payment/' . $code . '/min_order_total';
            $max = 'payment/' . $code . '/max_order_total';
            $emis[$code]['active'] = $this->_scopeConfig->getValue($active, ScopeInterface::SCOPE_STORE, $this->getStore());
            $emis[$code]['title'] = $this->_scopeConfig->getValue($title, ScopeInterface::SCOPE_STORE, $this->getStore());
            $emis[$code]['min_order'] = (int)$this->_scopeConfig->getValue($min, ScopeInterface::SCOPE_STORE, $this->getStore());
            $emis[$code]['max_order'] = (int)$this->_scopeConfig->getValue($max, ScopeInterface::SCOPE_STORE, $this->getStore());
        }

        return json_encode($emis);
    }

    public function buildCheckoutRequest() {
        $order = $this->checkoutSession->getLastRealOrder();
        $order->setStatus('pending_payment');
        $order->save();
        $billing_address = $order->getBillingAddress();

        $params = array();
        $params["key"] = $this->getConfigData("merchant_key");
        if ($this->getConfigData('account_type') == self::ACC_MONEY) {
            $params["service_provider"] = $this->getConfigData("service_provider");
        }
        $params["txnid"]       = $this->checkoutSession->getLastRealOrderId(); //substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $params["amount"]      = round($order->getBaseGrandTotal(), 2);
        $params["productinfo"] = $this->checkoutSession->getLastRealOrderId();
        $params["firstname"]   = $billing_address->getFirstName();
        $params["lastname"]    = $billing_address->getLastname();
        $params["city"]        = $billing_address->getCity();
        $params["state"]       = $billing_address->getRegion();
        $params["zip"]         = $billing_address->getPostcode();
        $params["country"]     = $billing_address->getCountryId();
        $params["email"]       = $order->getCustomerEmail();
        $params["phone"]       = $billing_address->getTelephone();
        $params["curl"]        = $this->getCancelUrl();
        $params["furl"]        = $this->getReturnUrl();
        $params["surl"]        = $this->getReturnUrl();

        $params["hash"] = $this->generatePayuHash($params['txnid'],
        $params['amount'], $params['productinfo'], $params['firstname'],
        $params['email']);

        return $params;
    }

    public function generatePayuHash($txnid, $amount, $productInfo, $name, $email) {
        $salt = $this->getConfigData('salt');

        $posted = array(
            'key' => $this->getConfigData("merchant_key"),
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productInfo,
            'firstname' => $name,
            'email' => $email,
            'udf1' => '',
            'udf2' => '',
            'udf3' => '',
            'udf4' => '',
            'udf5' => '',
            'udf6' => '',
            'udf7' => '',
            'udf8' => '',
            'udf9' => '',
            'udf10' => ''
        );

        $hashSequence = 'key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10';

        $hashVarsSeq = explode('|', $hashSequence);
        $hash_string = '';
        foreach ($hashVarsSeq as $hash_var) {
            $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
            $hash_string .= '|';
        }
        $hash_string .= $salt;
        return strtolower(hash('sha512', $hash_string));
    }

    //validate response
    public function validateResponse($returnParams) {
        if ($returnParams['status'] == 'success' &&
            !empty($returnParams['mihpayid']) &&
            is_numeric($returnParams['mihpayid']) &&
            ($returnParams['unmappedstatus'] == 'captured' || $returnParams['unmappedstatus'] == 'auth')) {
            return true;
        }

        return false;
    }

    // validate response hash
    public function validateHash($response) {

        $posted = array(
            'status'      => $response['status'],
            'udf10'       => '',
            'udf9'        => '',
            'udf8'        => '',
            'udf7'        => '',
            'udf6'        => '',
            'udf5'        => '',
            'udf4'        => '',
            'udf3'        => '',
            'udf2'        => '',
            'udf1'        => '',
            'email'       => $response['email'],
            'firstname'   => $response['firstname'],
            'productinfo' => $response['productinfo'],
            'amount'      => $response['amount'],
            'txnid'       => $response['txnid'],
            'key'         => $response['key']
        );
        $hashSequence = 'status|udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key';
        $hashVarsSeq = explode('|', $hashSequence);
        $hash_string = $this->getConfigData('salt') . '|';
        foreach ($hashVarsSeq as $hash_var) {
            $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] . '|' : '';
        }
        $hash_string= rtrim($hash_string, '|');
        $hash = strtolower(hash('sha512', $hash_string));
        if ($hash === $response['hash']) {
            return true;
        } else {
            return false;
        }
    }

    // get tampered message.
    private function getTamperedData(\Magento\Sales\Model\Order $order, $response) {
        $msg = 'Tampered data';
        if (trim($response['email']) != trim($order->getCustomerEmail())) {
            $msg .= ' email is ' . $order->getCustomerEmail() . ' => ' . $response['email'] . ',';
        }
        if (trim($response['firstname']) != trim($order->getCustomerFirstname())) {
            $msg .= ' firstname is ' . $order->getCustomerFirstname() . ' => ' . $response['firstname'] . ',';
        }
        if (trim($response['productinfo']) != trim($order->getIncrementId())) {
            $msg .= ' orderid is' . $order->getIncrementId() . ' => ' . $response['productinfo'] . ',';
        }
        if (round($response['amount']) != round($order->getGrandTotal())) {
            $msg .= ' orderid is' . $order->getGrandTotal() . ' => ' . $response['amount'] . ',';
        }
        if (trim($response['txnid']) != trim($order->getIncrementId())) {
            $msg .= ' transactionid is' . $order->getIncrementId() . ' => ' . $response['txnid'] . ',';
        }
        if (trim($response['key']) != trim($this->getConfigData("merchant_key"))) {
            $msg .= ' merchant key is' . $order->getIncrementId() . ' => ' . $response['key'];
        }
        if ($msg == 'Tampered data') {
            $msg .= ' status mis matched';
        }

        return $msg;
    }

    public function postProcessing(
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DataObject $payment,
        $response
    ) {
        $payment->setTransactionId($response['txnid']);
        $payment->setTransactionAdditionalInfo('payu_mihpayid', $response['mihpayid']);
        $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        $payment->setIsTransactionClosed(0);
        if (!empty($response['flag']) && $response['flag'] === 'fraud') {
            $payment->setAdditionalInformation('payu_order_status', 'fraud');
            $payment->place();
            $order->setStatus('fraud');
            $order->addStatusHistoryComment($this->getTamperedData($order, $response));
        } else if ($response['status'] === 'success' && $this->validateHash($response)) {
            $payment->setAdditionalInformation('payu_order_status', $response['unmappedstatus']);
            $payment->place();
            $order->setStatus('pending');
            $order->addStatusHistoryComment($response['unmappedstatus']);
        } else {
            $payment->setAdditionalInformation('payu_order_status', 'failed');
            $payment->place();
            $order->setStatus('failed');
            $order->addStatusHistoryComment($response['error_Message']);
        }
        $order->save();
    }

}
