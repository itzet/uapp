<?php

namespace Urjakart\Phonepe\Model;

use Magento\Sales\Api\Data\TransactionInterface;

class PhonePe extends \Magento\Payment\Model\Method\AbstractMethod {

    const PAYMENT_PHONEPE_CODE = 'phonepe';

    protected $_code = self::PAYMENT_PHONEPE_CODE;

    private $_status = '';

    protected $_supportedCurrencyCodes = [
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
    ];

    private $_txnCode = ['TRANSACTION_NOT_FOUND' => 'Payment was not initiated after coming to PhonePe container',
        'BAD_REQUEST' => 'The request is not valid',
        'AUTHORIZATION_FAILED' => 'X-VERIFY header is incorrect',
        'INTERNAL_SERVER_ERROR' => 'Something went wrong',
        'PAYMENT_SUCCESS' => 'Payment is successful',
        'PAYMENT_ERROR' => 'Payment failed',
        'PAYMENT_FAILED' => 'Payment failed',
        'PAYMENT_PENDING' => 'Payment is pending',
        'PAYMENT_CANCELLED' => 'Payment cancelled by merchant',
        'PAYMENT_DECLINED' => 'Payment declined by user'];

    /**
     * {inheritdoc}
     */
    protected $checkoutSession;

    /**
     * {inheritdoc}
     */
    protected $orderSender;

    /**
     * {inheritdoc}
     */
    protected $helper;

    /**
     * {inheritdoc}
     */
    protected $httpClientFactory;

    /**
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Urjakart\Phonepe\Helper\PhonePe $helper
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
        \Urjakart\Phonepe\Helper\PhonePe $helper,
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

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
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
        return $this->getConfigData('uat_url');
    }

    public function getApiVersion() {
        return trim($this->getConfigData('api_version'));
    }

    private function buildPayload() {

        $order = $this->checkoutSession->getLastRealOrder();
        $billing_address = $order->getBillingAddress();
        $orderId = $this->checkoutSession->getLastRealOrderId();
        $txn_id = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $payment = $order->getPayment();
        $this->setPaymentStatus($order, $payment, $txn_id);
        $params = [
            "merchantId"      => trim($this->getConfigData("merchant_key")),
            "transactionId"   => $txn_id,
            "merchantUserId"  => 'UK' . $orderId,
            "amount"          => (double)$order->getBaseGrandTotal() * 100,
            "merchantOrderId" => $orderId,
            "mobileNumber"    => $billing_address->getTelephone(),
            "message"         => "payment for order placed",
            "email"           => $order->getCustomerEmail(),
            "shortName"       => $billing_address->getFirstName()
        ];

        return trim(base64_encode(json_encode($params)));
    }

    private function setPaymentStatus(
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DataObject $payment,
        $txn_id
    ) {
        $payment->setTransactionId($txn_id);
        $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        $payment->setIsTransactionClosed(0);
        $payment->place();
        $order->setStatus('pending_payment');
        $order->save();
    }

    public function initiatDebitRequest()
    {
        $data = [];
        $salt_key = trim($this->getConfigData('salt'));
        $salt_index = trim($this->getConfigData('salt_index'));
        $request = $this->buildPayload();
        $header = hash('sha256', $request . '/'. $this->getApiVersion() . '/debit' . $salt_key) . "###" . $salt_index;
        $req = json_encode([ 'request' => $request ]);
        $wsUrl = $this->getCgiUrl() . '/'. $this->getApiVersion() . '/debit';
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $wsUrl);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, $req);
        curl_setopt($c, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'X-VERIFY:'. $header,
            'X-REDIRECT-URL:'. $this->getReturnUrl(),
            'X-REDIRECT-MODE: POST'
        ]);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        if (!curl_errno($c)) {
            switch ($http_code = curl_getinfo($c, CURLINFO_HTTP_CODE)) {
                case 302:
                    $res = explode('/', $response);
                    if (!empty($res[1])) {
                        $data['success'] = true;
                        $data['url'] = $this->getCgiUrl() . '/' . trim($res[1]);
                        $data['msg'] = '';
                    } else {
                        $data['success'] = false;
                        $data['url'] = '';
                        $data['msg'] = 'Unknown error!';
                    }
                    break;
                default:
                    $body = json_decode($response, 1);
                    $data['success'] = false;
                    $data['url'] = '';
                    $data['msg'] = $body['message'];
            }
        } else {
            $data['success'] = false;
            $data['url'] = '';
            $data['msg'] = 'debit initiate error!';
        }
        curl_close ($c);

        return $data;
    }

    private function isValidCheckSum($data)
    {
        if (!empty($data['checksum']))
        {
            $checksum = $data['checksum'];
            unset($data['checksum']);
            $resChecksum = implode('', $data);
            $resChecksum .= $this->getConfigData('salt');
            $resChecksum = hash('sha256', $resChecksum) . "###" . $this->getConfigData('salt_index');
            if ($resChecksum === $checksum) {
                return true;
            }
        }

        return false;
    }

    private function validateAmount($response){
        $order = $this->checkoutSession->getLastRealOrder();
        $amount = (double)$order->getBaseGrandTotal() * 100;
        if ($amount == $response['amount']) {
            return true;
        } else {
            return false;
        }
    }

    //validate response
    public function validateResponse($response) {
        if ($this->isValidCheckSum($response)) {
            if (($response['code'] === 'PAYMENT_SUCCESS' ||
                 $response['code'] === 'PAYMENT_PENDING') &&
                $this->verifyStatus($response) &&
                $this->validateAmount($response)
            ) {
                return true;
            }
        }

        return false;
    }

    public function responseError($code, $response) {
        if (array_key_exists($code, $this->_txnCode)) {
            return $this->_txnCode[$code];
        } elseif ($this->verifyStatus($response)) {
            return 'Transaction status verification has been failed!';
        } elseif ($this->validateAmount($response)) {
            return 'Amount not match in transaction response';
        } else {
            return 'Transaction failed, please choose other payment method to complete the transaction.';
        }
    }

    public function postProcessing(
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DataObject $payment,
        $response
    ) {
        $payment->setTransactionId($response['transactionId']);
        $payment->setTransactionAdditionalInfo('provider_reference_id', $response['providerReferenceId']);
        $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        $payment->setIsTransactionClosed(0);
        if ($response['code'] === 'PAYMENT_SUCCESS') {
            $payment->setAdditionalInformation('phone_pe_order_status', 'approved');
            $payment->place();
            $order->setStatus('prepaidverify');
        } elseif ($response['code'] === 'PAYMENT_PENDING') {
            $payment->setAdditionalInformation('phone_pe_order_status', 'pending');
            $payment->place();
            $order->setStatus('pending_payment');
            $order->addStatusHistoryComment($response['code']);
        } else {
            $payment->setAdditionalInformation('phone_pe_order_status', 'failed');
            $payment->place();
            $order->setStatus('failed');
            $order->addStatusHistoryComment($response['code']);
        }
        $order->save();
    }

    private function verifyStatus($response) {
        if ($this->_status === '') {
            $merchant_id = trim($this->getConfigData("merchant_key"));
            $salt_key = trim($this->getConfigData('salt'));
            $salt_index = trim($this->getConfigData('salt_index'));
            $request = '/' . $this->getApiVersion() . '/transaction/' . $merchant_id;
            $request .= '/' . $response['transactionId'] . '/status';
            $header = hash('sha256', $request . $salt_key) . "###" . $salt_index;
            $wsUrl = $this->getCgiUrl() . $request;
            $c = curl_init();
            curl_setopt($c, CURLOPT_URL, $wsUrl);
            curl_setopt($c, CURLOPT_HTTPHEADER, [
                'Content-Type:application/json',
                'X-VERIFY:' . $header
            ]);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            $status_res = curl_exec($c);
            curl_close($c);
            $status_res = json_decode($status_res, 1);
            if ($status_res['success']) {
                if ($status_res['code'] === 'PAYMENT_SUCCESS' || $status_res['code'] === 'PAYMENT_PENDING') {
                    $this->_status = true;
                } else {
                    $this->_status = false;
                }
            } else {
                $this->_status = false;
            }
            return $this->_status;
        } else {
            return $this->_status;
        }
    }
}
