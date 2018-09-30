<?php
namespace Urjakart\OrderTag\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class OrderSaveAfter implements ObserverInterface
{
    /**
    * @var \Magento\Backend\Model\Auth\Session
    */
    protected $authSession;

    /**
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->authSession = $authSession;
    }

    /**
     * @description This observer use for save sales order
     * placed by specific user group detail.
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        if ($order->getId() > 7527 && !$order->getTag()) {
            if ($this->authSession->getUser()) {
                $order->setTag($this->authSession->getUser()->getRole()->getRoleName());
            } else {
                $order->setTag('Online');
            }
            $order->save();
        }

        if (isset($_SESSION['uk_post_code'])) {
            unset($_SESSION['uk_post_code']);
        }
        if (isset($_SESSION['uk_cheque_msg'])) {
            unset($_SESSION['uk_cheque_msg']);
        }
        if (isset($_SESSION['cod_fee'])) {
            unset($_SESSION['cod_fee']);
        }
        if (isset($_SESSION['uk_cod_err_msg'])) {
            unset($_SESSION['uk_cod_err_msg']);
        }
    }
}
