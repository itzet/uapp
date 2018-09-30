<?php
namespace Urjakart\PinCodeValidator\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

class InvoiceCodFee implements ObserverInterface {

    public function execute(Observer $observer) {

        $invoice = $observer->getEvent()->getData('invoice');
        $order = $observer->getEvent()->getData('order');
        $invoice->setCodFee($order->getCodFee());
    }
}