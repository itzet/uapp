<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Popup;

use Magento\Customer\Model\AccountManagement;

class SendPass extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    /**
     * @description inherited method for popup reset password functionality.
     * @return string success or failure json data.
     */
    public function execute()
    {
        $email = trim($this->getRequest()->getParam('uk_email_forgot'));
        $token = trim($this->getRequest()->getParam('uk_token_forgot'));
        $result = [
            'success' => false
        ];
        if (!(\Zend_Validate::is($email, 'EmailAddress') &&
            strlen($email) <= 40)
        ) {
            $result['error'] = 'Email must be in a valid format and max 40 characters are allowed.';
        }
        if (empty($result['error']) && $this->tokenValidate($token, $email, $email)) {
            $model = $this->_customerFactory->create();
            $customer = $model->loadByEmail($email);
            if ($customer->getId()) {
                try {
                    $isSent = $this->_accountManagement->initiatePasswordReset(
                        $email,
                        AccountManagement::EMAIL_RESET
                    );
                    if ($isSent) {
                        $result['success'] = true;
                    }
                } catch (\Exception $e) {
                    $result['error'] = $e->getMessage();
                }
            } else {
                $result['error'] = __('Email not exist!');
            }
        } else {
            $result['error'] = __('Invalid email!');
        }

        return $this->_resultJsonFactory->create()->setData($result);
    }
}