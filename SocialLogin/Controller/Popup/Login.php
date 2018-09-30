<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Popup;

class Login extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    /**
     * @description inherited method for popup login functionality.
     * @return json as login success or failure.
     */
    public function execute()
    {
        $username = trim($this->getRequest()->getParam('uk_login_email'));
        $password = trim($this->getRequest()->getParam('uk_login_password'));
        $token = trim($this->getRequest()->getParam('uk-popup-login-token'));
        $result = [
            'success' => false
        ];
        if (!(\Zend_Validate::is($username, 'EmailAddress') &&
            strlen($username) <= 40)
        ) {
            $result['error'] = __('Email must be in a valid format and max 40 characters are allowed.');
        }
        if (empty($result['error']) && $this->tokenValidate($token, $username, $password)) {
            try {
                $customer = $this->_accountManagement->authenticate(
                    $username,
                    $password
                );
                $this->_getSession()->setCustomerDataAsLoggedIn($customer);
            } catch (\Exception $e) {
                $result['error'] = $e->getMessage();
            }
            if (!isset($result['error'])) {
                $result['success'] = true;
                $cookieManager = $this->_objectManager->get(\Magento\Framework\Stdlib\Cookie\PhpCookieManager::class);
                $cookieMetadataFactory = $this->_objectManager->get(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);
                if ($cookieManager->getCookie('mage-cache-sessid')) {
                    $metadata = $cookieMetadataFactory->createCookieMetadata();
                    $metadata->setPath('/');
                    $cookieManager->deleteCookie('mage-cache-sessid', $metadata);
                }
                /* custom cookie added */
                $cookie_name = "sess_validation";
                $value = "ses0985624567098";
                $sessionManager = $this->_objectManager->get('Magento\Framework\Session\SessionManagerInterface');
                $cookieManagerInterface = $this->_objectManager->get('Magento\Framework\Stdlib\CookieManagerInterface');
                $metadata = $cookieMetadataFactory->createPublicCookieMetadata()
                        ->setDuration($sessionManager->getCookieLifetime())
                        ->setPath($sessionManager->getCookiePath())
                        ->setDomain($sessionManager->getCookieDomain());
                $cookieManagerInterface->setPublicCookie($cookie_name, $value, $metadata);
                 /* custom cookie End here */
            }
        } else {
            $result['error'] = __('Incorrect email and/or password.');
        }

        return $this->_resultJsonFactory->create()->setData($result);
    }
}
