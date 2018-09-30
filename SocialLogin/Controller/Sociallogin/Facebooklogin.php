<?php

/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Sociallogin;

class Facebooklogin extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    const GENERAL = 1;

    /**
     * @description default controller method.
     * */
    public function execute()
    {
        if (!isset($_SESSION['is_logged_in'])) {
            $_SESSION['is_logged_in'] = 'facebooklogin';
        } elseif (stripos($_SERVER['REQUEST_URI'], $_SESSION['is_logged_in']) === false) {
            echo '<script> window.close() </script>'; exit;
        }

        if (!$this->getRequest()->getParams()) {
            if (isset($_SESSION['is_logged_in']))
                unset($_SESSION['is_logged_in']);
            echo '<script> window.close() </script>'; exit;
        }

        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        $fb = $this->_getFacebook();
        $response = $fb->validateLoginResponse($code, $state);
        if (!empty($response['token']) && empty($response['error'])) {
            $user = $fb->getFbUser($response['token']);
            if (empty($user['error']) && $user['user']) {
                $me = $user['user'];
                $userId = $me->getId();
                $data = $this->isFbUserExist($userId);
                if (empty($data['error'])) {
                    if ($data['data']) {
                        $email = !empty($data['data']) ? trim($data['data']) : '';
                        $_SESSION['email'] = $email;
                        echo '<script> window.close() </script>'; exit;
                    } else {
                        $_SESSION['fb_id'] = $me->getId();
                        $_SESSION['first_name'] = $me->getFirstName();
                        $_SESSION['last_name'] = $me->getLastName();
                        $_SESSION['token'] = $response['token'];
                        if ($me->getEmail()) {
                            $this->_register($me->getEmail());
                        } else {
                            echo $this->emailHtml();
                            exit;
                        }
                    }
                } else {
                    $this->_error($data['error']);
                }
            } else {
                $this->_error($user['error']);
            }
        } else {
            $this->_error($response['error']);
        }
    }

    /**
     * @description check facebook user exist or not.
     * @param $userId
     * @return array $data
     * */
    private function isFbUserExist($userId)
    {
        $fbData = [
            'data' => [],
            'error' => ''
        ];
        try {
            $tableName = $this->_getDbConnection()->getTableName('uk_facebook_customer');
            $query = 'select email from ' . $tableName . '  where facebook_id = :facebookId';
            $statement = $this->_getDbConnection()->prepare($query);
            $statement->bindValue('facebookId', $userId);
            $statement->execute();
            $user = $statement->fetch();
            $email = !empty($user['email']) ? $user['email'] : '';
            $fbData['data'] = $email;
        } catch (\Exception $e) {
            $fbData['error'] = $e->getMessage();
        }

        return $fbData;
    }

    /**
     * @description get the user email for facebook if not exist in data.
     * @param $error
     * @return string $html
     * */
    private function emailHtml($error = '')
    {
        $tableName = $this->_getDbConnection()->getTableName('social_audit_log');
        $data = [
            'social_id'   => $_SESSION['fb_id'],
            'social_type' => 'facebook',
            'first_name'  => $_SESSION['first_name'],
            'last_name'   => $_SESSION['last_name'],
            'social_data' => $_SESSION['token']
        ];
        $this->_getDbConnection()->insert($tableName, $data);
        $this->_getDbConnection()->closeConnection();
        $fb = $this->_getFacebook();
        $url = $fb->getFbLogoutUrl();
        if (!empty($url['url']) && empty($url['error'])) {
            $html = '<p style="text-align:center;font-size:15px;margin:35px 0 0 0;font-family: arial;">No email id is associated with this Facebook account,</p>';
            $html .= '<p style="text-align:center;font-size:15px;margin: 6px 0 35px 0;font-family: arial;">Please try login with other social account or Urjakart signin/signup !!</p>';
            $html .= '<a href="' . $url['url'] . '"';
            $html .= 'style="background:#F15A22;color: #FFFFFF;font-size: 18px;text-transform: uppercase;margin: 0 auto;';
            $html .= 'height: 40px;text-decoration: none;line-height: 40px;text-align: center;max-width: 123px;display: block;';
            $html .= 'font-family:arial;">Close</a>';
        } else {
            $html = '<p style="text-align:center;font-size:15px;margin:35px 0 0 0;font-family: arial;">No email id is associated with this Facebook account,</p>';
            $html .= '<p style="text-align:center;font-size:15px;margin: 6px 0 35px 0;font-family: arial;">Please try login with other social account or Urjakart signin/signup !!</p>';
            $html .= '<p style="color:red;text-align:center;font-family:arial;">' . $url['error'] . '</p>';
            $html .= '<p style="color:red;text-align: center;font-family: arial;">Some thing went wrong!</p>';
        }

        return $html;
    }

    /**
     * @description register facebook new user.
     * @param $email
     * */
    private function _register($email)
    {
        try {
            $_SESSION['email'] = $email;
            $password = 'NewUrja' . rand(1000,9999);
            $model = $this->_customerFactory->create();
            $customer = $model->setFirstname($_SESSION['first_name'])
                ->setLastname($_SESSION['last_name'])
                ->setEmail($email)
                ->setPassword($password)
                ->setGroupId(self::GENERAL);
            $this->_getSession()->regenerateId();
            $customer->save();
            $customerId = $customer->getDataModel()->getId();
            $tableName = $this->_getDbConnection()->getTableName('uk_facebook_customer');
            $data = [
                'facebook_id'  => $_SESSION['fb_id'],
                'customer_id'  => $customerId,
                'email'        => $email,
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
            } else {
                $this->_error($e->getMessage());
            }
        }

        echo '<script> window.close() </script>'; exit;
    }

    /**
     * @description register facebook new user for existing user of urjakart.
     * */
    private function _registerWithExistingEmail() {
        $id = $_SESSION['fb_id'];
        $firstName = $_SESSION['first_name'];
        $lastName = $_SESSION['last_name'];
        $token = $_SESSION['token'];
        $email = $_SESSION['email'];
        try {
            $model = $this->_customerFactory->create();
            $customer = $model->loadByEmail($email);
            $customerId = $customer->getDataModel()->getId();
            $tableName = $this->_getDbConnection()->getTableName('uk_facebook_customer');
            $data = [
                'facebook_id'  => $id,
                'customer_id'  => $customerId,
                'email'        => $email,
                'access_token' => $token,
                'first_name'   => $firstName,
                'last_name'    => $lastName
            ];
            $this->_getDbConnection()->insert($tableName, $data);
            $this->_getDbConnection()->closeConnection();
        } catch (\Exception $e) {
            echo $this->_error($e->getMessage());
            exit;
        }

        echo '<script> window.close() </script>'; exit;
    }
}