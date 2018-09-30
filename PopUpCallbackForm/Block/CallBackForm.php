<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\PopUpCallbackForm\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;

/**
 * Class CallBackForm
 *
 * @package Urjakart\PopUpCallbackForm\Block
 */
class CallBackForm extends Template
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $connection;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    private  $ref = null;

    /**
     * @var \Magento\Framework\Registry
     */
    private $product;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Urjakart\PopUpCallbackForm\Block\CallBackForm
     */
    private $errors = [];

    /**
     * @var \Urjakart\PopUpCallbackForm\Block\CallBackForm
     */
    private $pack = '';


    /**
     * CallBackForm constructor.
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Only use to create the object of database connection
     * Message manager, curl, Customer Session
     */
    public function executeForm() {
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $this->messageManager= $objectManager->get('Magento\Framework\Message\ManagerInterface');
        $this->curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
        $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $this->product = $objectManager->get('Magento\Framework\Registry')->registry('current_product');
        $this->removeOlderCSRFToken();
    }

    /**
     * Remove Older CSRF token from session
     * array length not more than 10.
     */
    private function removeOlderCSRFToken() {
        if (isset($_SESSION['csrf_token'])) {
            if (count($_SESSION['csrf_token']) > 10) {
                $token = $_SESSION['csrf_token'];
                array_shift($token);
                $_SESSION['csrf_token'] = $token;
            }
        }
    }

    /**
     * Get LoggedIn user data if user has loggedin
     * @return array
     */
    public function getLoggedInUserData() {
        $user = ['name' => '', 'email' => '', 'mobile' => ''];
        try {
            if ($this->customerSession->isLoggedIn()) {
                $user['name'] = $this->customerSession->getCustomer()->getName();
                $user['email'] = $this->customerSession->getCustomer()->getEmail();
                if ($this->customerSession->getCustomer()->getDefaultShippingAddress()) {
                    $user['mobile'] = $this->customerSession->getCustomer()->getDefaultShippingAddress()->getTelephone();
                }
            }
        } catch (\Exception $ex) {}

        return $user;
    }

    /**
     * Get Product url
     * @return string
     */
    public function getProductUrl() {
        return $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
    }

    /**
     * Get Product Name
     * @return string
     */
    public function getProductName() {
        $objectManager = ObjectManager::getInstance();
        $_product = $objectManager->create('\Magento\Catalog\Model\Product')->load($this->product->getId());
        $package = $_product->getResource()->getAttribute("units_package")->getFrontend()->getValue($_product);
        if($package >1) { $this->pack = ' (Pack of '.$package.')'; }else{ $this->pack = ''; }
        return $this->product->getName().$this->pack;
    }


    public function getSKU() {
        return $this->product->getSku();
    }

    /**
     * Get CSRF Token form
     * @return string
     */
    public function getCSRFToken() {
        $csrfToken = str_shuffle('UrjakartGauravAshutosh123456789');
        if (isset($_SESSION['csrf_token'])) {
            array_push($_SESSION['csrf_token'], $csrfToken);
        } else {
            $_SESSION['csrf_token'] = [$csrfToken];
        }
        return $csrfToken;
    }

    /**
     * Get email template html for urjakart
     * @return string
     */
    private function getMsgHtml($curl, $ref_no, $name, $email, $phone, $have) {
        $style = 'style="font-family:Arial, Helvetica, sans-serif;font-size:12px;line-height:19px;"';
        $msg = '<table align="left" border="0" cellpadding="0" cellspacing="0" width="550" >';
        $msg .= '<tr><td ' . $style . '>Hi There,<br></td></tr>';
        $msg .= '<tr><td height="10"></td></tr>';
        $msg .= '<tr><td ' . $style . '>Product URL : ' . $curl . '</td></tr>';
        $msg .= '<tr><td height="10"></td></tr>';
        $msg .= '<tr><td ' . $style . '>Ref Number : ' . $ref_no . '</td></tr>';
        $msg .= '<tr><td ' . $style . '>Name : ' . $name . '</td>';
        $msg .= '<tr><td height="10"></td></tr>';
        $msg .= '<tr><td ' . $style . '>Email : ' . $email . '</td></tr>';
        $msg .= '<tr><td height="10"></td></tr>';
        $msg .= '<tr><td ' . $style . '>Phone : ' . $phone . '</td></tr>';
        $msg .= '<tr><td height="10"></td></tr></table>';
        //$msg .= '<tr><td ' . $style . '>I Have : ' . $have . '</td></tr></table>';

        return $msg;
    }

    /**
     * Get email template html for client
     * @return string
     */
    private function getClientMsgHtml($ref_no, $name) {
        $style = 'style="font-family:Arial, Helvetica, sans-serif;font-size:12px;line-height:19px;"';
        $msg = '<table align="left" border="0" cellpadding="0" cellspacing="0" width="710" >';
        $msg .= '<tr><td ' . $style . '>Hi <span style="text-transform: capitalize;">'.$name.'</span>,<br><br>';
        $msg .= 'I am Lakhan and I will take up your request for callback. ';
        $msg .= 'Please use this ref# ' . $ref_no . ' for further communication.<br> ';
        $msg .= 'In the meantime, please help me get back to you asap. ';
        $msg .= 'Register on Urjakart to prioritize your request.<br><br>';
        $msg .= '1. Go to www.urjakart.com<br>';
        $msg .= '2. Click on Login/Register link on top right corner of webpage.<br>';
        $msg .= '3. Enter the required details.<br>';
        $msg .= '4. Submit and your account will be created.<br><br>';
        $msg .= 'After that, sit back and relax, I will get back to you shortly. </td></tr>';
        $msg .= '<tr><td height="15"></td></tr>';
        $msg .= '<tr><td ' . $style . '>Regards,<br>Lakhan Kumar<br></td></tr></table>';

        return $msg;
    }

    /**
     * Save form data in database
     * @return array
     */
    private function saveData($sub, $name, $phone, $curl, $have, $email,$sku) {

        $return = ['refno' => '', 'error' => ''];
        try {
            $tableName = $this->connection->getTableName('form_callback');
            $query = 'select ref_no from ' . $tableName . ' order by id desc limit 1';
            $ref_count = $this->connection->fetchRow($query);
            if (isset($ref_count['ref_no']) && $ref_count['ref_no'] == 'RC005000') {
                $ref_count['ref_no'] = 'RC' . sprintf('%06d', rand(4000, 5000));
            }
            $ref_count = isset($ref_count['ref_no']) ? $ref_count['ref_no'] : 5000;
            $ref_count = explode('C', $ref_count);
            $ref_count = (int)(end($ref_count)) + 1;
            $ref_no = 'RC' . sprintf('%06d', $ref_count);
            $data = [
                'ftitle' => $sub,
                'name' => $name,
                'phone_no' => $phone,
                'curl' => $curl,
                'ihave' => $have,
                'sku' => $sku,
                'email' => $email,
                'ref_no' => $ref_no
            ];
            $this->connection->insert($tableName, $data);
            $this->connection->closeConnection();
            $return['refno'] = $ref_no;
        } catch (\Exception $ex) {
            $return['error'] = 'Unknown error, please contact on +91-9811-282-778';
        }

        return $return;
    }

    /**
     * Send Email to both urjakart and client
     */
    private function sendEmail($to, $toClient, $name, $sub, $subClient, $msg, $msgClient, $mUrl, $bcc = '') {

        $AdminHeaders = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
            "From: Form Enquiry<info@urjakart.com>\r\n" .
            'Reply-To: ' . $toClient . "\r\n" .
            'X-Mailer: PHP/' . phpversion(); " \r\n";

        $headers = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
            'From: Lakhan from Urjakart<'.$to.">\r\n" .
            'X-Mailer: PHP/' . phpversion(); " \r\n";


        try {
            $mail_us = mail($to, $sub, $msg, $AdminHeaders);
            $mail_client = mail($toClient, $subClient, $msgClient, $headers);

            if ( $mail_us && $mail_client ) {
                $this->messageManager->addSuccessMessage(__('Hi, I have received your callback request, We will contact you soon. (lakhank@urjakart.com| +91-9811-282-778)'));

                if(!empty($_SESSION['backUrl']) && $mUrl == 'mreq') {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $redirect = $objectManager->get('\Magento\Framework\App\Response\Http');
                    $redirect->setRedirect($_SESSION['backUrl']);
                }
            } else {
                $this->messageManager->addErrorMessage(__('Unable to submit your request. Please, try again later'));
            }
        } catch (\Exception $ex) {
            $this->messageManager->addErrorMessage(__('Unable to submit your request. Please, try again later'));
        }
    }

    /**
     * Send Message to client
     */
    private function sendMessage($name, $phone, $ref_no) {

        $msg = 'Dear%20' . str_replace(' ', '%20', $name) . ',%20we';
        $msg .= '%20have%20received%20your%20callback%20request%20no.%20';
        $msg .= $ref_no . '.%20We%20will%20get%20back%20to%20you%20shortly.';
        $url = 'https://api.urjakart.com/sms.php';
        $url .= '?msg=' . $msg;
        $url .= '&rec=' . $phone;
        $url .= '&from=URJKRT';
        try {
            $this->curl->get($url);
        } catch (\Exception $ex) {
            return 'Unable to send message on your number, please check email.';
        }

    }

    /**
     * Remove CSRF token from session
     */
    private function removeToken($currentToken) {
        $arr_csrf_token = $_SESSION['csrf_token'];
        if (($key = array_search($currentToken, $arr_csrf_token)) !== false) {
            unset($arr_csrf_token[$key]);
            $_SESSION['csrf_token'] = $arr_csrf_token;
        }
    }

    /**
     * Validate callback options
     * @return boolean
     */
    private function validateCallBack($have) {
        $haveArr = [
            'Bulk Requirement',
            'I need support for my order',
            'I am confused; I need your help!'
        ];

        if (in_array(trim($have), $haveArr)) {
            return true;
        }

        return false;
    }

    /**
     * Validate callback form data
     * @return array
     */
    public function validateData($name, $toClient, $phone, $have) {

        if (!(strlen(trim($name)) >= 2 &&
            \Zend_Validate::is(str_replace(' ', '', $name), 'Alpha') &&
            strlen(trim($name)) <= 40)
        ) {
            $this->errors[] = 'Name must contain alphabets and max 40 characters are allowed.';
        }
        if (!(
            \Zend_Validate::is($phone, 'Digits')
            && strlen($phone) == 10
            && substr($phone,0,1) > 5)
        ) {
            $this->errors[] = 'Mobile number must be a valid 10 digit number.';
        }
        if (!(\Zend_Validate::is($toClient, 'EmailAddress') &&
            strlen(trim($toClient)) <= 40)
        ) {
            $this->errors[] = 'Email must be in a valid format and max 40 characters allowed.';
        }
        /*if (!$this->validateCallBack($have)) {
            $this->errors[] ='Invalid callback, please contact on +91-9599-304-982';
        }*/
    }

    /**
     * Process whole execution like validation, save, email, message
     * the callback detail.
     * @return Null
     */
    public function requestCallBackProcess() {

        $this->ref = null;
        unset($_SESSION['fName']);
        unset($_SESSION['lName']);
        unset($_SESSION['email']);
        unset($_SESSION['mobile']);
        unset($_SESSION['ref']);

        if($this->getRequest()->getPost('reqsubmit') !== 'reqsubmit'){
            return ;
        }
        $token = $this->getRequest()->getPost('csrf_token_post');
        if ( $token && isset($_SESSION['csrf_token']) && in_array($token, $_SESSION['csrf_token'])) {
            $to = 'lakhank@urjakart.com';
            $toClient = $this->getRequest()->getPost('email');
            $bcc ='urjakart@pipedrivemail.com,info@urjakart.com,skumar@urjakart.com,kathryn.tabitha@lakshyanet.com,saurabh.shrivastava@lakshyanet.com';
            $sub = 'Call Back Ref No - ';
            $mUrl = $this->getRequest()->getPost('murl');
            $have = $this->getRequest()->getPost('callback');
            $hsku = $this->getRequest()->getPost('sku');
            if(!empty($_SESSION['backUrl']) && $mUrl == 'mreq'){$productUrl = $_SESSION['backUrl'] ;}else{$productUrl = $this->getProductUrl();}
            $curl = $productUrl;
            $name = $this->getRequest()->getPost('name');
            $phone = $this->getRequest()->getPost('mobile');
            if(!empty($_SESSION['fsku'])){$sku = $_SESSION['fsku'];} else { $sku = $hsku;}
            $this->validateData($name, $toClient, $phone, $have);
            if ($this->errors) {
                foreach($this->errors as $error) {
                    $this->messageManager->addErrorMessage(__($error));
                }
                $this->removeToken($token);
                return;
            }
            $ref_no = $this->saveData($sub, $name, $phone, $curl, $have, $toClient, $sku);
            if ($ref_no['error']) {
                $this->messageManager->addErrorMessage(__($ref_no['error']));
                $this->removeToken($token);
                return;
            }
            $msg = $this->getMsgHtml($curl, $ref_no['refno'], $name, $toClient, $phone, $have);
            $msgClient = $this->getClientMsgHtml($ref_no['refno'], $name);
            if ($error = $this->sendMessage($name, $phone, $ref_no['refno'])) {
                $this->messageManager->addErrorMessage(__($error));
            }
            $sub = $sub.$ref_no['refno'];

            $this->ref = $ref_no['refno'];
            $nameData = explode(' ',trim($name),2);
            $fName = $nameData[0];
            $lName = $nameData[1];
            $_SESSION['fName'] = $fName;
            $_SESSION['lName'] = $lName;
            $_SESSION['email'] = $toClient;
            $_SESSION['mobile'] = $phone;
            $_SESSION['ref'] = $this->ref;
            $subClient ='Your Request For Call Back Ref.No - '.$ref_no['refno'];
            $this->sendEmail($to, $toClient, $name, $sub, $subClient, $msg, $msgClient, $mUrl, $bcc);
            $this->removeToken($token);

            return;
        }
    }

    public function ref() {
           return $this->ref;
   }
}
