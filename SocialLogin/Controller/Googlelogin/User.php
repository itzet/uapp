<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Googlelogin;

class User extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    /**
     * @var \Google_Client
     */
    private $_google = null;

    /**
     * @const user group.
     * */
    const GENERAL = 1;

    /**
     * @description controller default method.
     * */
    public function execute()
    {
        if (!isset($_SESSION['is_logged_in'])) {
            $_SESSION['is_logged_in'] = 'googlelogin';
        } elseif (stripos($_SERVER['REQUEST_URI'], $_SESSION['is_logged_in']) === false) {
            echo '<script> window.close() </script>'; exit;
        }

        $code = $this->getRequest()->getParam('code');
        if ($code) {
            try {
                $google = $this->getGoogle();
                $accessToken = $google->authenticate($code);
                if ($accessToken) {
                    $google->setAccessToken($accessToken);
                    $oauth2 = new \Google_Service_Oauth2($google);
                    $client = $oauth2->userinfo->get();
                    $data = $this->isGoogleUserExist($client->getId(), $client->getEmail());
                    if (empty($data['error'])) {
                        if ($data['data']) {
                            $_SESSION['email'] = $client->getEmail();
                            echo '<script> window.close() </script>'; exit;
                        } else {
                            $_SESSION['g_id'] = $client->getId();
                            $_SESSION['email'] = $client->getEmail();
                            $_SESSION['first_name'] = $client->getGivenName();
                            $_SESSION['last_name'] = $client->getFamilyName();
                            $_SESSION['token'] = $accessToken;
                            $this->_register();
                        }
                    } else {
                        $this->_error($data['error']);
                    }
                } else {
                    $this->_error('Google access token is empty');
                }
            } catch (\Exception $e) {
                $this->_error($e->getMessage());
            }
        } else {
            $this->_error('Google response code is empty');
        }
    }

    /**
     * @description Get google client object.
     * @return object
     * */
    private function getGoogle()
    {
        if (!$this->_google) {
            $this->_google = $this->_getGoogle()->newGoogle();
        }

        return $this->_google;
    }

    /**
     * @description Get google user exist or not.
     * @param string $email
     * @return array $data
     * */
    private function isGoogleUserExist($gId, $email)
    {
        $data = [
            'data' => [],
            'error' => ''
        ];
        try {
            $tableName = $this->_getDbConnection()->getTableName('uk_google_customer');
            $query = 'select email from ' . $tableName . '  where google_id = :googleId';
            $statement = $this->_getDbConnection()->prepare($query);
            $statement->bindValue('googleId', $gId);
            $statement->execute();
            $user = $statement->fetch();
            $email = !empty($user['email']) ? (trim($user['email']) === trim($email) ? true : false) : false;
            $data['data'] = $email;
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    /**
     * @description Register new google use with urjakart.
     * */
    private function _register()
    {
        try {
            $password = 'NewUrja' . rand(1000,9999);
            $model = $this->_customerFactory->create();
            $customer = $model->setFirstname($_SESSION['first_name'])
                ->setLastname($_SESSION['last_name'])
                ->setEmail($_SESSION['email'])
                ->setPassword($password)
                ->setGroupId(self::GENERAL);
            $this->_getSession()->regenerateId();
            $customer->save();
            $customerId = $customer->getDataModel()->getId();
            $tableName = $this->_getDbConnection()->getTableName('uk_google_customer');
            $data = [
                'customer_id'  => $customerId,
                'google_id'    => $_SESSION['g_id'],
                'email'        => $_SESSION['email'],
                'access_token' => $_SESSION['token'],
                'first_name'   => $_SESSION['first_name'],
                'last_name'    => $_SESSION['last_name']
            ];
            $this->_getDbConnection()->insert($tableName, $data);
            $this->_getDbConnection()->closeConnection();
            $_SESSION['password'] = $password;
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'same email already exists') > 0) {
                $this->_registerWithExistingEmail();
                exit;
            } else {
                $this->_error($e->getMessage());
            }
        }

        echo '<script> window.close() </script>'; exit;
    }

    /**
     * @description register user with existing user of urjakart.
     * */
    private function _registerWithExistingEmail() {
        try {
            $model = $this->_customerFactory->create();
            $customer = $model->loadByEmail($_SESSION['email']);
            $customerId = $customer->getDataModel()->getId();
            $tableName = $this->_getDbConnection()->getTableName('uk_google_customer');
            $data = [
                'customer_id'  => $customerId,
                'google_id'    => $_SESSION['g_id'],
                'email'        => $_SESSION['email'],
                'access_token' => $_SESSION['token'],
                'first_name'   => $_SESSION['first_name'],
                'last_name'    => $_SESSION['last_name']
            ];
            $this->_getDbConnection()->insert($tableName, $data);
            $this->_getDbConnection()->closeConnection();
        } catch (\Exception $e) {
            $this->_error($e->getMessage());
        }

        echo '<script> window.close() </script>'; exit;
    }
}