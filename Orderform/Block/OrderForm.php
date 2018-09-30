<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Orderform\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;

/**
 * Class OrderForm
 *
 * @package Urjakart\Orderform\Block
 */
class OrderForm extends Template
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

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Urjakart\Orderform\Block\OrderForm
     */
    private $errors = [];

    /**
     * @var \Urjakart\Bulkorderform\Block\BulkOrderForm
     */
    private $regex = ["/^[a-zA-Z0-9\'._+,\"\/\- ]+$/"];

    /**
     * OrderForm constructor.
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

    protected function _prepareLayout()
    {
        /**
         *Set canonical tags
         **/
        $cUrl = rtrim($this->_storeManager->getStore()->getBaseUrl(), '/') . $_SERVER['REQUEST_URI'];
        $this->pageConfig->addRemotePageAsset(
            $cUrl,
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );
    }
    /**
     * Only use to create the object of database connection
     * Message manager, curl, Customer Session.
     */
    public function executeForm() {
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $this->messageManager= $objectManager->get('Magento\Framework\Message\ManagerInterface');
        $this->curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
        $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
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
     * Get Order Form Action url
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl(
            'order',
            [
                '_secure' => true,
                '_use_rewrite' => true
            ]
        );
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
    private function getMsgHtml($ref_no, $name, $email, $phone, $products) {
        $style = 'style="text-align:left;width:100%;border-bottom:1px solid #eee;font-size:12px;line-height:135%;color:#464647;border:1px solid #1e439b"';
        //$tr = 'style="background-color:#f5f5f5;"';
        $dh = 'style="padding:10px;border:1px solid #ccc;border-bottom:0;"';
        $dd = 'style="width:60%;padding:10px;border:1px solid #ccc;text-transform:capitalize;border-bottom:0;border-left: 0;"';
        $msg .= '<div style="color:#464647">Hi,</div><br><br>';
        $msg .= '<div style="width:700px;color:#fff; text-align:left; margin:0; font-family:Verdana,Arial,Helvetica,sans-serif">';
        $msg .= '<div style="color:#464647;margin-bottom:22px;">Customer <span style="text-transform:capitalize"> ' . $name . '</span>, has submitted ';
        $msg .= 'an Order form from Page : ' . $this->getFormAction() . '<br></div>';
        $msg .= '<h2 style="background-color:#1e439b;color:#ffffff;border: 1px solid #1e439b;padding: 8px 25px;display: inline-block;margin: 0;">Order form...</h2>';
        $msg .= '<table cellpadding="0" cellspacing="0" ' . $style . '>';
        $msg .= '<tr ' . $tr . '><td ' . $dh . '><b>Ref Number</b></td>';
        $msg .= '<td style="width:60%;padding:10px;border:1px solid #ccc;text-transform:capitalize;border-left: 0;">' . $ref_no . '</td></tr>';
        $msg .= '<tr ' . $tr . '><td ' . $dh . '><b>Name</b></td>';
        $msg .= '<td ' . $dd . '>' . $name . '</td></tr>';
        $msg .= '<tr ' . $tr . '><td ' . $dh . '><b>Email</b></td>';
        $msg .= '<td ' . $dd . '><span style="text-transform:none;">' . $email . '</span></td></tr>';
        $msg .= '<tr ' . $tr . '><td ' . $dh . '><b>Mobile No</b></td>';
        $msg .= '<td ' . $dd . '>' . $phone . '</td></tr>';
        foreach($products as $product => $quantity) {
            $msg .= '<tr ' . $tr . '><td ' . $dh . '><b>Product Name</b></td>';
            $msg .= '<td ' . $dd . '>' . $product . '</td></tr>';
            $msg .= '<tr ' . $tr . '><td ' . $dh . '><b>Quantity</b></td>';
            $msg .= '<td style="width:60%;padding:10px;border-top:1px solid #ccc;">' . $quantity . '</td></tr>';
        }
        $msg .= '</table></div>';

        return $msg;
    }

    /**
     * Get email template html for client
     * @return string
     */
    private function getClientMsgHtml($ref_no, $name, $products) {
        $style = 'style="width:700px;text-align:left;font-family:Verdana,Arial,Helvetica,sans-serif; margin:0; line-height:25px;"';
        $count = 1;
        $msg = '<div ' . $style . '>';
        $msg .= '<div style="color:#464647;">';
        $msg .= 'Hi <span style="text-transform: capitalize">' . $name .'</span>,<br><br>';
        $msg .= 'Thank you for shopping with us. You will receive a ';
        $msg .= 'confirmation email soon. Reach me anytime via ';
        $msg .= 'email or call me at +91-9599-304-982. I would be glad to take up your request. ';
        $msg .= 'Please use this ref# ' . $ref_no . ' for further communication.</div>';
        $msg .= '<div style="font-size:12px;margin-top:20px;color:#464647; line-height:25px;">';
        $msg .= '<h2 style="background-color:#1e439b;color:#ffffff;border: 1px solid #1e439b;padding: 8px 25px;display: inline-block;margin: 0;">Your Order Summary</h2>';
        $msg .= '<table cellspacing="0" cellpadding="0" style="text-align:left;border:1px solid #1e439b;font-size:12px;color:#464647;width:100%;background:#eaeaea;">';
        $msg .= '<tr>';
        $msg .= '<td style="width:20%;padding:8px;background:#eaeaea;font-size:12px;border-bottom: 1px solid #cccccc;color:#000000;"><b>SNo.</b></td>';
        $msg .= '<td style="width:60%;padding:8px;background:#eaeaea;font-size:12px;border-bottom: 1px solid #cccccc;color:#000000;"><b>Product</b></td>';
        $msg .= '<td style="width:20%;padding:8px;background:#eaeaea;font-size:12px;border-bottom: 1px solid #cccccc;color:#000000;"><b>Quantity</b></td>';
        $msg .= '</tr>';
        foreach($products as $product => $quantity) {
            $msg .= '<tr style="background-color:#fff"><td style="width: 20%;padding: 8px;border: 1px solid #eee;">' . $count . '</td>';
            $msg .= '<td style="width: 60%;padding: 8px;border-right:1px solid #eee;">' . $product . '</td>';
            $msg .= '<td style="width: 20%;padding: 8px;border-right:1px solid #eee;">' . $quantity . '</td></tr>';
            $count++;
        }
        $msg .= '</table></div></div>';
        $msg .= '<div style="font-size:12px;margin-top:20px;color:#464647; line-height:18px;">';
        $msg .= '<br>Regards,<br>Subham from Urjakart<br>+91-9599-304-982<br>skumar@urjakart.com</div>';

        return $msg;
    }

    /**
     * Save form data in database
     * @return array
     */
    private function saveData($sub, $name, $phone, $email, $productData) {

        $return = ['refno' => '', 'error' => ''];
        try {
            $tableName = $this->connection->getTableName('form_order');
            $query = 'select ref_no from ' . $tableName . ' order by id desc limit 1';
            $ref_count = $this->connection->fetchRow($query);
            if (isset($ref_count['ref_no']) && $ref_count['ref_no'] == 'FC005000') {
                $ref_count['ref_no'] = 'FC' . sprintf('%06d', rand(4000, 5000));
            }
            $ref_count = isset($ref_count['ref_no']) ? $ref_count['ref_no'] : 5000;
            $ref_count = explode('C', $ref_count);
            $ref_count = (int)(end($ref_count)) + 1;
            $ref_no = 'FC' . sprintf('%06d', $ref_count);
            foreach ($productData as $product => $qty) {
                $data = [
                    'ftitle'        => $sub,
                    'customer_name' => $name,
                    'mobile_no'     => $phone,
                    'email'         => $email,
                    'qty'           => $qty,
                    'product_name'  => $product,
                    'ref_no'        => $ref_no
                ];
                $this->connection->insert($tableName, $data);
            }
            $this->connection->closeConnection();
            $return['refno'] = $ref_no;
        } catch (\Exception $ex) {
            $return['error'] = 'Unknown error, please contact on +91-9599-304-982';
        }

        return $return;
    }

    /**
     * Send Email to both urjakart and client
     */
    private function sendEmail($to, $toClient, $name, $sub, $msg, $msgClient, $bcc = '') {
        $headers = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
            'From: ' .$name."<".$to.">\r\n" .
            'Reply-To: ' . $to . "\r\n" .
            'X-Mailer: PHP/' . phpversion(); " \r\n";

        try {
            $mail_us = mail($to, $sub, $msg, $headers);
            $mail_client = mail($toClient, $sub, $msgClient, $headers);

            if ( $mail_us && $mail_client ) {
                $this->messageManager->addSuccessMessage(__('Thanks for your order, We (Subham , +91-9599-304-982) will contact you shortly for order confirmation.'));
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
     * Validate order form data
     * @return array
     */
    public function validateData($name, $toClient, $phone, $productData) {

        if (!(strlen(trim($name)) >= 2 &&
            \Zend_Validate::is(str_replace(' ', '', $name), 'Alpha') &&
            strlen(trim($name)) <= 40)
        ) {
            $this->errors[] = 'Name must contain alphabets with space and max 40 characters are allowed.';
        }
        if (!(
            \Zend_Validate::is($phone, 'Digits')
            && strlen($phone) == 10
            && substr($phone,0,1) > 5)
        ) {
            $this->errors[] = 'Mobile number must be valid 10 digit number.';
        }
        if (!(\Zend_Validate::is($toClient, 'NotEmpty') &&
            \Zend_Validate::is($toClient, 'EmailAddress') &&
            strlen(trim($toClient)) <= 40)
        ) {
            $this->errors[] = 'Email must be in a valid format and max 40 characters are allowed.';
        }
        if (!$productData) {
            $this->errors[] = 'Product "" must be valid and max 50 characters are allowed.';
        }
        foreach($productData as $product => $qty) {

            if (!(\Zend_Validate::is($product, 'Regex', $this->regex) &&
                strlen(trim($product)) <= 50)
            ) {
                $this->errors[] = 'Product "' . $product . '" must be valid and max 50 characters are allowed.';
            }
            if (!(\Zend_Validate::is($qty, 'Digits') && $qty >= 1 && $qty <= 9999)) {
                $this->errors[] = 'Quantity of "' . $product . '" must be min 1 and max 9999 allowed.';
            }
        }
    }

    /**
     * Process whole execution like validation, save, email, message
     * the order detail.
     * @return Null
     */
    public function quickOrderProcess() {
        if (!empty($_REQUEST['subscription'])) {
            if (strpos($_REQUEST['subscription'], 'Thank') > -1 || strpos($_REQUEST['subscription'], 'confirmation')) {
                $this->messageManager->addSuccessMessage(__($_REQUEST['subscription']));
            } else {
                $this->messageManager->addErrorMessage(__($_REQUEST['subscription']));
            }
            unset($_REQUEST['subscription']);
        }
        $token = $this->getRequest()->getPost('csrf_token_post');
        if ($token && isset($_SESSION['csrf_token']) && in_array($token, $_SESSION['csrf_token'])) {
            $to = 'skumar@urjakart.com';
            $toClient = $this->getRequest()->getPost('email');
            $bcc ='urjakart@pipedrivemail.com,info@urjakart.com,kathryn.tabitha@lakshyanet.com,saurabh.shrivastava@lakshyanet.com';
            $sub = 'Order Form';
            $name = trim($this->getRequest()->getPost('name'));
            $phone = trim($this->getRequest()->getPost('mobile'));
            $products = $this->getRequest()->getPost('productName');
            $qtys = $this->getRequest()->getPost('qty');
            $title = 'Urjakart.com';
            $productData = array_combine($products, $qtys);
            $productData = array_filter($productData, function($value) { return trim($value) !== ''; }, ARRAY_FILTER_USE_KEY);
            $this->validateData($name, $toClient, $phone, $productData);
            if ($this->errors) {
                foreach($this->errors as $error) {
                    $this->messageManager->addErrorMessage(__($error));
                }
                $this->removeToken($token);
                return;
            }
            $ref_no = $this->saveData($sub, $name, $phone, $toClient, $productData);
            if ($ref_no['error']) {
                $this->messageManager->addErrorMessage(__($ref_no['error']));
                $this->removeToken($token);
                return;
            }
            $msg = $this->getMsgHtml($ref_no['refno'], $name, $toClient, $phone, $productData);
            $msgClient = $this->getClientMsgHtml($ref_no['refno'], $name, $productData);
            if ($error = $this->sendMessage($name, $phone, $ref_no['refno'])) {
                $this->messageManager->addErrorMessage(__($error));
            }
            $this->sendEmail($to, $toClient, $title, $sub, $msg, $msgClient, $bcc);
            $this->removeToken($token);

            return;
        }
    }
}