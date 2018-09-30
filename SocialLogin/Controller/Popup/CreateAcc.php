<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Popup;

class CreateAcc extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    /** @var CreateAcc */
    private $_error = '';

    /**
     * @description inherited method for popup create new customer account functionality.
     * @return json as new customer account creation success or failure.
     */
    public function execute()
    {
        $result = ['success' => false];
        if ($this->_getSession()->isLoggedIn() || !$this->_getRegistration()->isAllowed()) {
            $result['error'] = 'Already logged in or not allowed!';
        } elseif (!$this->getRequest()->isPost()) {
            $result['error'] = 'Invalid Request!';
        } else {
            $firstName = trim($this->getRequest()->getParam('firstname'));
            $lastName = trim($this->getRequest()->getParam('lastname'));
            $pass = trim($this->getRequest()->getParam('pass'));
            $gst = trim($this->getRequest()->getParam('gst'));
            $email = trim($this->getRequest()->getParam('email'));
            $token = trim($this->getRequest()->getParam('auth-new-token'));
            if ($this->isInvalidRequest($firstName, $lastName, $email, $pass, $gst)) {
                $result['error'] = $this->_error;
            } elseif (!$this->tokenValidate($token, $email, $pass)) {
                $result['error'] = 'Request is not authorized!';
            } else {
                try {
                    $model = $this->_customerFactory->create();
                    $customer = $model->setFirstname($firstName)
                        ->setLastname($lastName)
                        ->setEmail($email)
                        ->setPassword($pass)
                        ->setTaxvat($gst)
                        ->setGroupId(1);
                    $this->_getSession()->regenerateId();
                    $customer->save();
                    $customer->getDataModel()->setConfirmation($customer->getRandomConfirmationKey());
                    $this->_eventManager->dispatch('customer_register_success',
                        ['customer' => $customer]
                    );
                    $result['success'] = true;
                    $customer->sendNewAccountEmail();
                    $this->_getSession()->setCustomerAsLoggedIn($customer);
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

                } catch (\Exception $e) {
                    $result['error'] = $e->getMessage();
                }
            }
        }

        return $this->_resultJsonFactory->create()->setData($result);
    }

    /**
     * @description validate posted data is invalid or not.
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $pass
     * @param string $passConfirm
     * @return boolean true/false.
     */
    private function isInvalidRequest($firstName, $lastName, $email, $pass, $gst)
    {
        if (!(strlen($firstName) >= 2 &&
            \Zend_Validate::is($firstName, 'Alpha') &&
            strlen($firstName) <= 40)
        ) {
            $this->_error .= 'First name must contain alphabets and max 40 characters are allowed.' . "\n";
        }
        if (!(strlen($lastName) >= 2 &&
            \Zend_Validate::is($lastName, 'Alpha') &&
            strlen($lastName) <= 40)
        ) {
            $this->_error .= 'Last name must contain alphabets and max 40 characters are allowed.' . "\n";
        }
        if (!(\Zend_Validate::is($email, 'NotEmpty') &&
            \Zend_Validate::is($email, 'EmailAddress') &&
            strlen($email) <= 40)
        ) {
            $this->_error .= 'Email must be in a valid format and max 40 characters are allowed.' . "\n";
        }
        if (strlen($pass) < 6) {
            $this->_error .= 'Password must be at least 6 characters.' . "\n";
        }
        $gstRegex = ["/^[0-9]{2}[(a-z)(A-Z)]{5}[0-9]{4}[(a-z)(A-Z)]{1}[0-9]{1}[(a-z)(A-Z)]{1}[(a-z)(A-Z)(0-9)]{1}$/"];
        if (!empty($gst) && !\Zend_Validate::is($gst, 'Regex', $gstRegex)) {
            $this->_error .=  'GST number must be in a valid format (Like: 07AAFCN0263N1ZA).';
        }

        if ($this->_error === '') {
            return false;
        } else {
            return true;
        }
    }
}
