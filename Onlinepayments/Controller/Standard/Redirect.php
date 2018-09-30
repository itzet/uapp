<?php

namespace Urjakart\Onlinepayments\Controller\Standard;

use \Urjakart\Onlinepayments\Controller\OnlinePaymentAbstract;

class Redirect extends OnlinePaymentAbstract {

    public function execute() {
        if (!$this->getRequest()->isAjax()) {
            $this->_cancelPayment();
            $this->getResponse()->setRedirect(
                    $this->getCheckoutHelper()->getUrl('checkout')
            );
        }

        $quote = $this->getQuote();
        $email = $this->getRequest()->getParam('email');

        if ($this->getCustomerSession()->isLoggedIn()) {
            $this->getCheckoutSession()->loadCustomerQuote();
            $quote->updateCustomerData($this->getQuote()->getCustomer());
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
        } else {
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
        }

        $quote->setCustomerEmail($email);
        $quote->save();

        $params = [];
        $params["fields"] = $this->getPaymentMethod()->buildCheckoutRequest();
        $params["url"] = $this->getPaymentMethod()->getCgiUrl();
        // This is only to save row response data in uk_payment_response table.
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $db_conn = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $tableName = $db_conn->getTableName('uk_payment_response');
        $order = $this->getOrder();
        $rowResponse = [
            'order_id' => $order->getIncrementId(),
            'type_name' => $order->getPayment()->getMethod()
        ];
        $db_conn->insert($tableName, $rowResponse);
        $db_conn->closeConnection();
        // end
        return $this->resultJsonFactory->create()->setData($params);
    }
}
