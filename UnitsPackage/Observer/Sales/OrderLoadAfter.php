<?php
namespace Urjakart\UnitsPackage\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class OrderLoadAfter implements ObserverInterface
{
    /**
     * @description This observer use for save units_package's
     * Product attribute data with sales order items data.
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        $items = $order->getAllItems();
        foreach ($items as $item) {
            $product = $item->getProduct();
            $unitsPackage = $product->getData('units_package');
            $item->setUnitsPackage($unitsPackage);
        }
        $order->save();
    }
}