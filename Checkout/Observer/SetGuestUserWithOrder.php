<?php
namespace Urjakart\Checkout\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\App\ObjectManager;

class SetGuestUserWithOrder implements ObserverInterface {

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    public function execute(Observer $observer) {

        $order = $observer->getEvent()->getOrder();
        if (empty($order->getCustomerId()) && $order->getCustomerIsGuest()) {
            if ($this->customerSession->getCustomer()->getId()) {
                $order->setCustomerId($this->customerSession->getCustomer()->getId())
                    ->setCustomerIsGuest(0)
                    ->save();
            } else {
                $email = $order->getCustomerEmail();
                $objectManager = ObjectManager::getInstance();
                $customerFactory = $objectManager->get('Magento\Customer\Model\Customer');
                $customer = $customerFactory->loadByEmail($email);
                $order->setCustomerId($customer->getId())
                    ->setCustomerIsGuest(0)
                    ->save();
            }
        }
    }
}