<?php
namespace Urjakart\PinCodeValidator\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\App\ObjectManager;

class AddedCodFee implements ObserverInterface {

    const API_URL = 'https://api.urjakart.com/pincheck.php';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var \Magento\Sales\Model\Order\Address\Validator
     */
    protected $addressValidator;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $customerData;

    private $objectManager;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Sales\Model\Order\Address\Validator $addressValidator
     * @param \Magento\Customer\Api\Data\CustomerInterface $customerData
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Sales\Model\Order\Address\Validator $addressValidator,
        \Magento\Customer\Api\Data\CustomerInterface $customerData
    ) {
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->addressValidator = $addressValidator;
        $this->customerData = $customerData;
    }

    public function execute(Observer $observer) {

        $this->objectManager = ObjectManager::getInstance();
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $code = $method->getCode();
        if ($code === 'cashondelivery' && $order->getStatus() === 'pending' && $this->isBackendCodAvailable($order)) {
            $postCode  = $order->getShippingAddress()->getPostcode();
            $curl = $this->objectManager->get('Magento\Framework\HTTP\Client\Curl');
            $wsUrl = self::API_URL . '?token=' . $this->getToken() . '&pin=' . trim($postCode);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 1);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, 1);
            $curl->get($wsUrl);
            if ($curl->getStatus() !== 200) {
                throw new \Exception('COD API Response Error.');
            }
            $response = json_decode($curl->getBody(),1);
            $status = !empty($response['head']['status']) ? $response['head']['status'] : false;
            if ($status) {
                $fee = !empty($response['body']['data']['cod_fees']) ? $response['body']['data']['cod_fees'] : 0;
                if ($fee) {
                    if ($order->getCodFee() == 0) {
                        $order->setCodFee($fee);
                        $grandTotal = $order->getGrandTotal() + $fee;
                        $order->setGrandTotal($grandTotal);
                        $order->setBaseGrandTotal($grandTotal);
                    }
                } else {
                    $messageManager= $this->objectManager->get('Magento\Framework\Message\ManagerInterface');
                    $messageManager->addErrorMessage(__($response['body']['msg']));
                }
            }
        }
    }

    private function getToken() {
        $connection = $this->objectManager
            ->get('Magento\Framework\App\ResourceConnection')
            ->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $tableName = $connection->getTableName('access_list');
        $token = $connection->fetchRow("select token from $tableName");

        return !empty($token['token']) ? $token['token'] : $token;
    }

    private function isBackendCodAvailable($order){
        $codAttribute = [];
        foreach ($order->getAllItems() as $item) {
            $productId = $item->getProductId();
            $product = $this->objectManager->get('Magento\Catalog\Model\Product')->load($productId);
            $codAttribute[] = strtolower($product->getAttributeText('cod_available'));
        }

        return in_array('no', $codAttribute) ? false : true;
    }
}
