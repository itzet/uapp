<?php
namespace Urjakart\Adminregisteremail\Observer\Backend;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\App\ObjectManager;
use \Magento\Store\Model\ScopeInterface;

class AdminRegisterEmailObserver implements ObserverInterface {

    public function execute(Observer $observer) {

        $customerData = $observer->getCustomerDataObject();
        $email = trim($customerData->getEmail());
        $objectManager = ObjectManager::getInstance();
        $url = $objectManager->get('Magento\Framework\Url');
        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $find = $url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        $userRegisterByOrder = strpos($find, 'sales/order_create');
        $userRegisterByOrder = (int)$userRegisterByOrder > 0 ? true : false;
        if ($userRegisterByOrder) {
            $tableName = $connection->getTableName('customer_create_email');
            $query = 'select * from ' . $tableName . ' where email = :email';
            $stmt = $connection->prepare($query);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $emailData = $stmt->fetch();
            if (!$emailData) {
                $data = [
                    'email' => $email,
                    'send_status' => 1
                ];
                $connection->insert($tableName, $data);
            } elseif (isset($emailData['send_status']) && $emailData['send_status'] == 1) {
                $data = [
                    'email' => $email,
                    'send_status' => $emailData['send_status'] + 1
                ];
                $connection->update($tableName, $data, 'email="' . $email . '"');
                $tableName = $connection->getTableName('customer_entity');
                $query = 'select * from ' . $tableName . ' where email = :email';
                $stmt = $connection->prepare($query);
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $userData = $stmt->fetch();
                $password = is_null($userData['password_hash']) ? true : false;
                if ($password) {
                    $password = 'NewUrja' . rand(1000, 9999);
                    $customer = $objectManager->get('Magento\Customer\Model\Customer');
                    $customer->loadByEmail($email);
                    $customer->setPassword($password);
                    $customer->save();
                    $name = $customer->getName();
                    $scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
                    $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
                    $_transportBuilder = $objectManager->get('Magento\Framework\Mail\Template\TransportBuilder');
                    $inlineTranslation = $objectManager->get('Magento\Framework\Translate\Inline\StateInterface');
                    $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_ADMINHTML, 'store' => $storeManager->getStore()->getId());
                    $templateVars = array(
                        'store' => $storeManager->getStore(),
                        'customer_name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'change' => $url->getUrl('customer/account/edit',
                            [
                                '_secure' => true,
                                '_use_rewrite' => true
                            ])
                    );
                    $ac_name = $scopeConfig->getValue('trans_email/ident_custom1/name', ScopeInterface::SCOPE_STORE);
                    $ac_email = $scopeConfig->getValue('trans_email/ident_custom1/email', ScopeInterface::SCOPE_STORE);
                    $from = array('email' => $ac_email, 'name' => $ac_name);
                    $inlineTranslation->suspend();
                    $to = array($email);
                    $transport = $_transportBuilder->setTemplateIdentifier('uk_user_template')
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
                    $transport->sendMessage();
                    $inlineTranslation->resume();
                }
            }
        }
    }
}