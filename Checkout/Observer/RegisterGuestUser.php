<?php
namespace Urjakart\Checkout\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

class RegisterGuestUser implements ObserverInterface {

    /**
     * @constant \Magento\Customer\Model\Customer
     */
    const GENERAL = 1;

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

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Sales\Model\Order\Address\Validator $addressValidator
     * @param \Magento\Customer\Api\Data\CustomerInterface $customerData
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Sales\Model\Order\Address\Validator $addressValidator,
        \Magento\Customer\Api\Data\CustomerInterface $customerData,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->addressValidator = $addressValidator;
        $this->customerData = $customerData;
        $this->inlineTranslation = $inlineTranslation;
    }

    public function execute(Observer $observer) {

        $objectManager = ObjectManager::getInstance();
        $order = $observer->getEvent()->getOrder();
        $addresses = $order->getAddresses();
        $email = $order->getCustomerEmail();
        if (
            $this->customerSession->isLoggedIn()
            || !$this->accountManagement->isEmailAvailable($email)
            || !$this->validateAddresses($addresses)

        ) {
            return '';
        }
        $isBillingSameAsShipping = true;
        $billingSameAsShipping = '1';
        $password = 'NewUrja' . rand(1000,9999);
        $firstName = $order->getShippingAddress()->getFirstname();
        $lastName  = $order->getShippingAddress()->getLastname();
        $countryId = $order->getShippingAddress()->getCountryId();
        $postCode  = $order->getShippingAddress()->getPostcode();
        $city      = $order->getShippingAddress()->getCity();
        $state     = $order->getShippingAddress()->getRegion();
        $stateId   = $order->getShippingAddress()->getRegionId();
        $telephone = $order->getShippingAddress()->getTelephone();
        $gst       = $order->getShippingAddress()->getVatId();
        $fax       = $order->getShippingAddress()->getFax();
        $company   = $order->getShippingAddress()->getCompany();
        $street    = $order->getShippingAddress()->getStreet();
        $streetB   = $order->getBillingAddress()->getStreet();
        $street1   = isset($street[1]) ? $street[1] : '';
        $streetB1   = isset($streetB[1]) ? $streetB[1] : '';
        $firstNameB = $order->getBillingAddress()->getFirstname();
        if (!(
            trim($street[0]) === trim($streetB[0])
            && trim($street1) === trim($streetB1)
            && trim($firstName) === trim($firstNameB)
        )) {
            $isBillingSameAsShipping = false;
            $billingSameAsShipping = '0';
        }
        if (!$isBillingSameAsShipping) {
            $lastNameB  = $order->getBillingAddress()->getLastname();
            $countryIdB = $order->getBillingAddress()->getCountryId();
            $postCodeB  = $order->getBillingAddress()->getPostcode();
            $cityB      = $order->getBillingAddress()->getCity();
            $stateB     = $order->getBillingAddress()->getRegion();
            $stateIdB   = $order->getBillingAddress()->getRegionId();
            $telephoneB = $order->getBillingAddress()->getTelephone();
            $gstB       = $order->getBillingAddress()->getVatId();
            $faxB       = $order->getBillingAddress()->getFax();
            $companyB   = $order->getBillingAddress()->getCompany();
        }
        $customer  = $this->customerFactory->create();
        $customer->setEmail($email);
        $customer->setFirstname($firstName);
        $customer->setLastname($lastName);
        $customer->setPassword($password);
        $customer->setGroupId(self::GENERAL);
        $customer->save();
        $customerId = $customer->getId();
        $this->customerSession->setCustomerId($customerId);
        $this->customerData->setFirstname($firstName);
        $this->customerData->setLastname($lastName);
        $this->customerSession->regenerateId();
        $this->customerSession->loginById($customerId);
        $this->customerSession->setSignupstate(2);
        $this->customerSession->setCustomerCompany($company);
        $this->customerSession->setCustomerMobile($telephone);
        $cookieManager = $objectManager->get(\Magento\Framework\Stdlib\Cookie\PhpCookieManager::class);
        $cookieMetadataFactory = $objectManager->get(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);
        if ($cookieManager->getCookie('mage-cache-sessid')) {
            $metadata = $cookieMetadataFactory->createCookieMetadata();
            $metadata->setPath('/');
            $cookieManager->deleteCookie('mage-cache-sessid', $metadata);
        }
        $this->customerSession->setShippingGst($gst);
        $this->customerSession->setBillingGst($gstB);
        $addressFactory = $objectManager->get('Magento\Customer\Model\AddressFactory');
        $customerAddress = $addressFactory->create();
        $customerAddress->setCustomerId($customerId)
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setCountryId($countryId)
            ->setPostcode($postCode)
            ->setCity($city)
            ->setRegion($state)
            ->setRegionId($stateId)
            ->setTelephone($telephone)
            ->setVatId($gst)
            ->setFax($fax)
            ->setCompany($company)
            ->setStreet($street)
            ->setIsDefaultBilling($billingSameAsShipping)
            ->setIsDefaultShipping(true)
            ->setSaveInAddressBook(1)
            ->save();
        if (!$isBillingSameAsShipping) {
            $customerAddressB = $addressFactory->create();
            $customerAddressB->setCustomerId($customerId)
                ->setFirstname($firstNameB)
                ->setLastname($lastNameB)
                ->setCountryId($countryIdB)
                ->setPostcode($postCodeB)
                ->setCity($cityB)
                ->setRegion($stateB)
                ->setRegionId($stateIdB)
                ->setTelephone($telephoneB)
                ->setVatId($gstB)
                ->setFax($faxB)
                ->setCompany($companyB)
                ->setStreet($streetB)
                ->setIsDefaultBilling(true)
                ->setIsDefaultShipping($billingSameAsShipping)
                ->setSaveInAddressBook(1)
                ->save();
        }
        $order->setCustomerFirstname($firstName);
        $order->setCustomerLastname($lastName);
        $order->setCustomerGroupId(self::GENERAL);

        $scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $url = $objectManager->get('Magento\Framework\Url');
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $_transportBuilder = $objectManager->get('Magento\Framework\Mail\Template\TransportBuilder');
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeManager->getStore()->getId());
        $templateVars = array(
            'store' => $storeManager->getStore(),
            'customer_name' => $firstName . ' ' . $lastName,
            'email' => $email,
            'password' => $password,
            'change' => $url->getUrl('customer/account/edit',
                [
                    '_secure' => true,
                    '_use_rewrite' => true
                ])
        );
        $ac_name = $scopeConfig->getValue('trans_email/ident_custom1/name',ScopeInterface::SCOPE_STORE);
        $ac_email = $scopeConfig->getValue('trans_email/ident_custom1/email',ScopeInterface::SCOPE_STORE);
        $from = array('email' => $ac_email, 'name' => $ac_name);
        $this->inlineTranslation->suspend();
        $to = array($email);
        $transport = $_transportBuilder->setTemplateIdentifier('uk_guest_template')
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to)
            ->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();

    }

    private function validateAddresses($addresses) {
        foreach ($addresses as $address) {
            $result = $this->addressValidator->validateForCustomer($address);
            if (is_array($result) && !empty($result)) {
                return false;
            }
        }
        return true;
    }
}