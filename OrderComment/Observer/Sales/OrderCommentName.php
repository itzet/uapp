<?php
namespace Urjakart\OrderComment\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class OrderCommentName implements ObserverInterface
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
     * comment by added admin user name or system generated.
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        $data = $order->getStatusHistories();
        $history = $data[count($data)-1];
        if ($this->authSession->getUser()) {
            $name = $this->authSession->getUser()->getName();
            $user = '<b> By ' . $name . '</b>';
            if ($history) {
                if (strpos($history->getComment(), $name) === false) {
                    $history->setComment('| ' . $history->getComment() . $user);
                }
            }
            if ($order->getStatus() == 'canceled') {
                $order->addStatusHistoryComment('| '. $user, $order->getStatus());
            }
        } else {
            $user = '<b> By System</b>';
            if ($history) {
                if (strpos($history->getComment(), 'By System') === false) {
                    $history->setComment('| ' . $history->getComment() . $user);
                }
            }
        }
        $order->save();
    }
}
