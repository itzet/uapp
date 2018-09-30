<?php
/**
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
namespace Urjakart\SocialLogin\Controller\Sociallogin;

class LinkedInLogin extends \Urjakart\SocialLogin\Controller\SocialLogin
{
    const GENERAL = 1;

    /**
     * @var \Happyr\LinkedIn\LinkedIn
     */
    private $_linkedIn = null;

    /**
     * @description default controller method.
     * */
    public function execute()
    {
        if (!isset($_SESSION['is_logged_in'])) {
            $_SESSION['is_logged_in'] = 'linkedinlogin';
        } elseif (stripos($_SERVER['REQUEST_URI'], $_SESSION['is_logged_in']) === false) {
            echo '<script> window.close() </script>'; exit;
        }
        if ($this->getRequest()->isPost() &&
            !empty($this->getRequest()->getParam('ext_button'))) {
            if (isset($_SESSION['is_logged_in']))
                unset($_SESSION['is_logged_in']);
            echo '<script> window.close() </script>'; exit;
        }
        $state = $this->getRequest()->getParam('state');
        $linkedIn = $this->getLinkedIn();
        $this->_getLinkedIn()->setLinkedResponseData($state);
        if ($linkedIn->isAuthenticated()) {
            $user = $linkedIn->get('v1/people/~:(id,firstName,lastName,emailAddress)');
            $accessToken = $linkedIn->getAccessToken();
            $userId = !empty($user['id']) ? $user['id'] : '';
            $data = $this->isLinkUserExist($userId);
            if (empty($data['error'])) {
                if ($data['data']) {
                    $email = !empty($data['data']) ? trim($data['data']) : '';
                    $_SESSION['email'] = $email;
                    echo '<script> window.close() </script>'; exit;
                } else {
                    $_SESSION['link_id'] = $userId;
                    $_SESSION['first_name'] = !empty($user['firstName']) ? $user['firstName'] : '';
                    $_SESSION['last_name'] = !empty($user['lastName']) ? $user['lastName'] : '';
                    $_SESSION['token'] = $accessToken;
                    $email = !empty($user['emailAddress']) ? $user['emailAddress'] : '';
                    if ($email) {
                        $this->_register($email);
                    } else {
                        echo $this->emailHtml();
                        exit;
                    }
                }
            } else {
                $this->_error($data['error']);
            }
        } else {
            $this->_error('Not authorized!');
        }
    }

    /**
     * @description Get linked-in client object.
     * @return object
     * */
    private function getLinkedIn()
    {
        if (!$this->_linkedIn) {
            $this->_linkedIn = $this->_getLinkedIn()->newLinkedIn();
        }

        return $this->_linkedIn;
    }

    /**
     * @description check linked-in user exist or not.
     * @param $userId
     * @return array $data
     * */
    private function isLinkUserExist($userId)
    {
        $linkData = [
            'data' => [],
            'error' => ''
        ];
        try {
            $tableName = $this->_getDbConnection()->getTableName('uk_linked_in_customer');
            $query = 'select email from ' . $tableName . '  where linked_in_id = :linkedInId';
            $statement = $this->_getDbConnection()->prepare($query);
            $statement->bindValue('linkedInId', $userId);
            $statement->execute();
            $user = $statement->fetch();
            $email = !empty($user['email']) ? $user['email'] : '';
            $linkData['data'] = $email;
        } catch (\Exception $e) {
            $linkData['error'] = $e->getMessage();
        }

        return $linkData;
    }

    /**
     * @description get the user email for linked-in if not exist in data.
     * @param $error
     * @return string $html
     * */
    private function emailHtml($error = '')
    {
        $tableName = $this->_getDbConnection()->getTableName('social_audit_log');
        $data = [
            'social_id'   => $_SESSION['link_id'],
            'social_type' => 'linkedIn',
            'first_name'  => $_SESSION['first_name'],
            'last_name'   => $_SESSION['last_name'],
            'social_data' => $_SESSION['token']
        ];
        $this->_getDbConnection()->insert($tableName, $data);
        $this->_getDbConnection()->closeConnection();
        $html = '<html><head><script type="text/javascript" src="https://platform.linkedin.com/in.js">
        api_key: ' . $this->_helperData->getLinkedInClientKey() . '
        authorize: true
        onLoad: onLinkedInLoad
        </script><script type="text/javascript">
        function onLinkedInLoad() {
            if (IN.User.isAuthorized()) {
                IN.User.logout(function () {
                    console.log("logout successfully!");
                }, {});
            }
        }</script></head><body>';
        $html .= '<p style="text-align:center;font-size:15px;margin:35px 0 0 0;font-family: arial;">No email id is associated with this LinkedIn account,</p>';
        $html .= '<p style="text-align:center;font-size:15px;margin: 6px 0 35px 0;font-family: arial;">Please try login with other social account or Urjakart signin/signup !!</p>';
        $html .= '<form method="post">';
        $html .= '<button type="submit" name="ext_button" value="ext_button"';
        $html .= 'style="background: #F15A22;color: #FFFFFF;font-size: 18px;text-transform: uppercase;margin: 0 auto;';
        $html .= 'height: 40px;text-decoration: none;line-height: 40px;text-align: center;width: 123px;display: block;';
        $html .= 'font-family: arial;border: 0;cursor: pointer;">Close</button></form>';
        $html .= '<div style="visibility: hidden"><script type="in/Login"></script></div></body></html>';

        return $html;
    }

    /**
     * @description register linked-in new user.
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
            $tableName = $this->_getDbConnection()->getTableName('uk_linked_in_customer');
            $data = [
                'linked_in_id' => $_SESSION['link_id'],
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
     * @description register linked-in new user for existing user of urjakart.
     * */
    private function _registerWithExistingEmail() {
        try {
            $model = $this->_customerFactory->create();
            $customer = $model->loadByEmail($_SESSION['email']);
            $customerId = $customer->getDataModel()->getId();
            $tableName = $this->_getDbConnection()->getTableName('uk_linked_in_customer');
            $data = [
                'linked_in_id' => $_SESSION['link_id'],
                'customer_id'  => $customerId,
                'email'        => $_SESSION['email'],
                'access_token' => $_SESSION['token'],
                'first_name'   => $_SESSION['first_name'],
                'last_name'    => $_SESSION['last_name']
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