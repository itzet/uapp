<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Sociallogin;

use Magento\Store\Model\ScopeInterface;

class CheckLogin extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    /**
     * @description default controller method.
     * */
    public function execute()
    {
        $email = $_SESSION['email'];
        $result = [
            'success' => false,
            'error' => ''
        ];
        if (!empty($email)) {
            try {
                $model = $this->_customerFactory->create();
                $customer = $model->loadByEmail($email);
                $this->_getSession()->setCustomerDataAsLoggedIn($customer->getDataModel());
            } catch (\Exception $e) {
                $result['error'] = $e->getMessage();
            }
            if (empty($result['error'])) {
                $result['success'] = true;
                if (!empty($_SESSION['first_name']) &&
                    !empty($_SESSION['last_name']) &&
                    !empty($_SESSION['password'])) {
                    $this->sendEmailToCustomer(
                        $_SESSION['first_name'],
                        $_SESSION['last_name'],
                        $_SESSION['email'],
                        $_SESSION['password']
                    );
                }
                $this->_getSession()->regenerateId();
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
                $this->unSetSession();
            }
        }
        if (isset($_SESSION['is_logged_in']))
            unset($_SESSION['is_logged_in']);

        return $this->_resultJsonFactory->create()->setData($result);
    }

    /**
     * @description unset the session after login with social option.
     * */
    public function unSetSession()
    {
        if (isset($_SESSION['fb_id']))
            unset($_SESSION['fb_id']);
        if (isset($_SESSION['g_id']))
            unset($_SESSION['g_id']);
        if (isset($_SESSION['link_id']))
            unset($_SESSION['link_id']);
        if (isset($_SESSION['email']))
            unset($_SESSION['email']);
        if (isset($_SESSION['password']))
            unset($_SESSION['password']);
        if (isset($_SESSION['first_name']))
            unset($_SESSION['first_name']);
        if (isset($_SESSION['last_name']))
            unset($_SESSION['last_name']);
    }

    /**
     * @description send email to customer after register by social login for password.
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     * @return array success or failure data
     * */
    private function sendEmailToCustomer($firstName, $lastName, $email, $password)
    {
        try {
            $inlineTranslation = $this->_objectManager->get('Magento\Framework\Translate\Inline\StateInterface');
            $_transportBuilder = $this->_objectManager->get('Magento\Framework\Mail\Template\TransportBuilder');
            $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_getStoreId());
            $templateVars = array(
                'store' => $this->_storeManager->getStore(),
                'customer_name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => $password,
                'change' => $this->_helperData->getAccountEditUrl()
            );
            $ac_name = $this->_scopeConfig->getValue('trans_email/ident_custom1/name',ScopeInterface::SCOPE_STORE);
            $ac_email = $this->_scopeConfig->getValue('trans_email/ident_custom1/email',ScopeInterface::SCOPE_STORE);
            $from = array('email' => $ac_email, 'name' => $ac_name);
            $inlineTranslation->suspend();
            $to = array($email);
            $transport = $_transportBuilder->setTemplateIdentifier('uk_facebook_template')
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($to)
                ->getTransport();
            $transport->sendMessage();
            $inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return null;
    }
}
