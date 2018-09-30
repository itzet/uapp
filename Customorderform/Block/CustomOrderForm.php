<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Customorderform\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class CustomOrderForm
 *
 * @package Urjakart\Customorderform\Block
 */
class CustomOrderForm extends Template
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
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $mediaPath;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Urjakart\Customorderform\Block\CustomOrderForm
     */
    private $extension = ['png','jpg','jpeg','gif','doc','docx','txt','odt','xls','ods'];

    /**
     * @var \Urjakart\Customorderform\Block\CustomOrderForm
     */
    private $regex = ["/^[a-zA-Z0-9\'._+,()\"\/\- ]+$/"];

    /**
     * @var \Urjakart\Customorderform\Block\CustomOrderForm
     */
    private $gstRegex = ["/^[0-9]{2}[(a-z)(A-Z)]{5}[0-9]{4}[(a-z)(A-Z)]{1}[0-9]{1}[(a-z)(A-Z)]{1}[(a-z)(A-Z)(0-9)]{1}$/"];

    /**
     * @var \Urjakart\Customorderform\Block\CustomOrderForm
     */
    private $regexdesc = ["/^[a-zA-Z0-9\'._+,\"\/\-(){}!?@\[\]\\ ]*$/"];

    /**
     * @var \Urjakart\Customorderform\Block\CustomOrderForm
     */
    private $errors = [];

    /**
     * CustomOrderForm constructor.
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
     * Message manager, curl, Customer Session, Filesystem
     * Media path.
     */
    public function executeForm() {
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $this->messageManager= $objectManager->get('Magento\Framework\Message\ManagerInterface');
        $this->curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
        $this->fileSystem = $objectManager->get('\Magento\Framework\Filesystem');
        $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $this->mediaPath  = $this->fileSystem->getDirectoryWrite(DirectoryList::PUB)->getAbsolutePath();
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
     * Get BulkOrder Form Action url
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl(
            'custom-order',
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
    private function getMsgHtml($ref_no, $name, $email, $phone, $company, $products, $reason, $comment, $payment, $nature, $gst_no) {
        $style = 'style="text-align:left; width:100%;border-bottom:1px solid #ccc;font-size:12px;line-height:135%;color:#464647;border:1px solid #1e439b;"';
        //$tr = 'style="background-color:#f5f5f5"';
        $dh = 'style="padding:10px;border:1px solid #eee;border-bottom: 0;border-left: 0;"';
        $dd = 'style="width:60%;padding:10px;border:1px solid #ccc;border: 1px solid #ccc;border-bottom: 0;border-left: 0;"';
        $msg = '<div style="width:700px;color:#fff; text-align:left; margin:0;font-family:Verdana,Arial,Helvetica,sans-serif;">';
        $msg .= '<span style="color:#464647;">Hi,</span><br><br>';
        $msg .= '<h2 style="background-color: #1e439b;color: #ffffff;border: 1px solid #1e439b;padding: 8px 25px;display: inline-block;margin: 0;">For Custom Order Requirement</h2>';
        $msg .= '<table cellpadding="0" cellspacing="0" ' . $style . '>';
        $msg .= '<tr><td ' . $dh . '><b>Ref Number</b></td>';
        $msg .= '<td ' . $dd . '>' . $ref_no . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Name</b></td>';
        $msg .= '<td ' . $dd . '>' . $name . '</td></tr>';
        $msg .= '<tr><td style="border-top: 1px solid #ccc;border-right: 1px solid #ccc;"><b>Email</b></td>';
        $msg .= '<td ' . $dd . '>' . $email . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Mobile No</b></td>';
        $msg .= '<td ' . $dd . '>' . $phone . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Company Name</b></td>';
        $msg .= '<td ' . $dd . '>' . $company . '</td></tr>';
        foreach($products as $product => $quantity) {
            $msg .= '<tr><td ' . $dh . '><b>Product Name</b></td>';
            $msg .= '<td ' . $dd . '>' . $product . '</td></tr>';
            $msg .= '<tr><td style="border-top: 1px solid #ccc;border-right: 1px solid #ccc;"><b>Quantity</b></td>';
            $msg .= '<td ' . $dd . '>' . $quantity . '</td></tr>';
        }
        $msg .= '<tr><td ' . $dh . '><b>Reason for buying</b></td>';
        $msg .= '<td ' . $dd . '>' . $reason . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Nature of business</b></td>';
        $msg .= '<td ' . $dd . '>' . $nature . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>GST number</b></td>';
        $msg .= '<td ' . $dd . '>' . $gst_no . '</td></tr>';
        $msg .= '<tr><td style="border-top: 1px solid #ccc;border-right: 1px solid #ccc;"><b>Remark</b></td>';
        $msg .= '<td ' . $dd . '>' . $comment . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Payment Method</b></td>';
        $msg .= '<td style="padding:10px;border-top:1px solid #ccc;">' . $payment . '</td></tr>';
        $msg .= '</table></div>';

        return $msg;
    }

    /**
     * Get email template html for client
     * @return string
     */
    private function getClientMsgHtml($ref_no, $name) {
        $style = 'style="width:700px;text-align:left;font-family:Verdana,Arial,Helvetica,sans-serif; margin:0; line-height:25px;"';
        $msg = '<div ' . $style . '>';
        $msg .= '<div style="color:#464647;">';
        $msg .= '<span style="text-transform:capitalize;color:#464647;">Hi ' . $name .'</span>,<br><br>';
        $msg .= 'Thank you for sharing your requirement with us. ';
        $msg .= 'I will share our quotation for your requirement asap. ';
        $msg .= 'It generally takes me 3 - 5 hours to revert back to you. ';
        $msg .= 'If your need is very urgent and you can\'t wait,';
        $msg .= 'do give me a call at my mobile: +91-9599-304-982. ';
        $msg .= 'I would be glad to take up your request. ';
        $msg .= 'Please use this ref# ' . $ref_no . ' for further communication.<br>';
        $msg .= '<br>Thank you for your time.</div>';
        $msg .= '<div style="font-size:12px;margin-top:20px;color:#464647; line-height:18px;">';
        $msg .= 'Regards,<br>Subham from Urjakart<br>+91-9599-304-982<br>skumar@urjakart.com</div></div>';

        return $msg;
    }

    /**
     * Save form data in database
     * @return array
     */
    private function saveData($sub, $name, $phone, $email, $company, $payment, $productData, $reason, $nature, $gst_no, $comment, $imageName) {
        $return = ['refno' => '', 'error' => ''];
        try {
            $tableName = $this->connection->getTableName('form_customorder');
            $query = 'select ref_no from ' . $tableName . ' order by id desc limit 1';
            $ref_count = $this->connection->fetchRow($query);
            if (isset($ref_count['ref_no']) && $ref_count['ref_no'] == 'CO005000') {
                $number = rand(4000, 5000);
                $ref_count['ref_no'] = 'CO' . sprintf('%06d', $number);
            }
            $ref_count = isset($ref_count['ref_no']) ? $ref_count['ref_no'] : 5000;
            $ref_count = explode('O', $ref_count);
            $ref_count = (int)(end($ref_count)) + 1;
            $ref_no = 'CO' . sprintf('%06d', $ref_count);
            foreach ($productData as $product => $qty) {

                $data = [
                    'ftitle' => $sub,
                    'name' => $name,
                    'phone_no' => $phone,
                    'email' => $email,
                    'company_name' => $company,
                    'pay_method' => $payment,
                    'product_name' => $product,
                    'product_image' => $imageName,
                    'qty' => $qty,
                    'reason' => $reason,
                    'gst_no' => $gst_no,
                    'nature' => $nature,
                    'ref_no' => $ref_no,
                    'remark' => $comment

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
    private function sendEmail($to, $toClient, $name, $sub, $msgHeader, $msgClient, $imageName, $strContent, $from = '', $bcc = '') {

        if ($imageName && $strContent) {
            $strSid = md5(uniqid(time()));
            $Header = "From: " . $name . "<" . $from . ">" . PHP_EOL;
            $Header .= "Reply-To: " . $toClient . PHP_EOL;
            $Header .= 'Bcc:' . $bcc . "" . PHP_EOL;
            $Header .= "MIME-Version: 1.0" . PHP_EOL;
            $Header .= "Content-Type: multipart/mixed; boundary=\"" . $strSid . "\"" . PHP_EOL . PHP_EOL;
            $strHeader = "--" . $strSid . PHP_EOL;
            $strHeader .= "Content-type: text/html; charset=utf-8" . PHP_EOL;
            $strHeader .= "Content-Transfer-Encoding:7bit" . PHP_EOL . PHP_EOL;
            $strHeader .= $msgHeader . PHP_EOL;
            $strHeader .= "--" . $strSid . PHP_EOL;
            $strHeader .= "Content-Type: application/octet-stream; name=\"" . $imageName . "\"" . PHP_EOL;
            $strHeader .= "Content-Transfer-Encoding: base64" . PHP_EOL;
            $strHeader .= "Content-Disposition: attachment; filename=\"" . $imageName . "\"" . PHP_EOL . PHP_EOL;
            $strHeader .= $strContent . PHP_EOL;
        } else {
            $Header = 'MIME-Version: 1.0' . "\r\n" .
                'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                "From: " . $name . "<" . $from . ">" . "\r\n" .
                'Reply-To: ' . $toClient . "\r\n" .
                'X-Mailer: PHP/' . phpversion(); " \r\n";
            $strHeader = $msgHeader . PHP_EOL;
        }

        $headers = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
            "From: 'Subham from Urjakart'<".$to.">\r\n" .
            'Reply-To: ' . $to . "\r\n" .
            'X-Mailer: PHP/' . phpversion(); " \r\n";

        try {
            $mail_us = mail($to, $sub, $strHeader, $Header, "-f$from");
            $mail_client = mail($toClient, $sub, $msgClient, $headers, "-f$to");

            if ( $mail_us && $mail_client ) {
                $this->messageManager->addSuccessMessage(__('Thanks for your RFQ, We(skumar@urjakart.com, +91-9599-304-982) will share quote from our sellers shortly.'));
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

        $msg = 'Dear%20' . $name . ',%20we';
        $msg .= '%20have%20received%20your%20Custom%20Order%20request%20Your%20ref%20no.%20';
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
     * Validate document/image of bulkorder
     * @return string
     */
    private function docValidate($doc) {

        $docName = $doc['name'];
        $docSize = $doc['size'];
        $docError = $doc['error'];
        $docErrorName = $docName;

        if ($docSize) {
            if ($docSize > 2500000) {
                $this->errors[] = 'File must less than 2.5MB';
            }
        }
        if ($docName) {
            $img = explode('.', $docName);
            $img1 = isset($img[0]) ? $img[0] : '';
            $img2 = isset($img[1]) ? $img[1] : '';
            if (!in_array(strtolower($img2), $this->extension)) {
                $this->errors[] = 'File must be .png,.jpeg,.jpg,.gif,.doc,.docx,.txt,.xls,.ods only allowed';
            }
            $docName = $img1 . '_' . uniqid(time());
            $docName .= '.' . $img2;
        }
        if ($docError && $docErrorName) {
            $this->errors[] = 'Invalid file or unexpected error!';
        }

        return $docName;
    }

    /**
     * Validate buying reason
     * @return boolean
     */
    private function validateReason($reason) {
        $reasons = [
            'End use',
            'Resell'
        ];
        if (in_array(trim($reason), $reasons)) {
            return true;
        }

        return false;
    }

    /**
     * Validate payment types
     * @return boolean
     */
    private function validatePaymentType($paymentType) {
        $paymentTypes = [
            'Advance payment',
            'Cash on delivery',
            'My preference'
        ];
        if (in_array(trim($paymentType), $paymentTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Validate Nature of business
     * @return boolean
     */
    private function validateNature($nature) {
        $natures = [
            'Exporter',
            'Retailer',
            'Service Provider',
            'Trader',
            'Wholesaler'
        ];
        if (in_array(trim($nature), $natures)) {
            return true;
        }

        return false;
    }

    /**
     * Validate bulkorder form data
     * @return array
     */
    private function validateData($name, $email, $phone, $company, $productData, $reason, $payment, $nature, $gst_no, $remark) {

        if (!(strlen(trim($name)) >= 2 &&
            \Zend_Validate::is(str_replace(' ', '', $name), 'Alpha') &&
            strlen(trim($name)) <= 40)
        ) {
            $this->errors[] = 'Full name must be alphabets with space and max 40 characters are allowed.';
        }
        if (!(\Zend_Validate::is($email, 'NotEmpty') &&
            \Zend_Validate::is($email, 'EmailAddress') &&
            strlen(trim($email)) <= 40)
        ) {
            $this->errors[] = 'Email must be in valid format and max 40 characters are allowed.';
        }
        if (!(
            \Zend_Validate::is($phone, 'Digits')
            && strlen($phone) == 10
            && substr($phone,0,1) > 5)
        ) {
            $this->errors[] = 'Mobile number must be valid 10 digits number.';
        }
        foreach($productData as $product => $qty) {

            if (!(strlen(trim($name)) >= 2 &&
                \Zend_Validate::is($product, 'Regex', $this->regex) &&
                strlen(trim($product)) <= 50)
            ) {
                $this->errors[] = 'Product "' . $product . '" must be valid and max 50 characters are allowed.';
            }
            if (!(\Zend_Validate::is($qty, 'Digits') && $qty >= 1 && $qty <= 9999)) {
                $this->errors[] = 'Quantity of "' . $product . '" must be min 1 and max 9999 allowed.';
            }
        }
        if (!empty($gst_no) && !\Zend_Validate::is($gst_no, 'Regex', $this->gstRegex)) {
            $this->errors[] = 'GST number must be valid format (Like: 07AAFCN0263N1ZA).';
        }
        if (!$this->validateReason($reason)) {
            $this->errors[] = 'Reason must be a valid option.';
        }
        if (!(\Zend_Validate::is($company, 'NotEmpty') &&
            \Zend_Validate::is($company, 'Regex', $this->regex) &&
            strlen(trim($company)) <= 50)
            ) {
            $this->errors[] = 'Company name must be valid and max 50 characters are allowed.';
        }
        if (!$this->validatePaymentType($payment)) {
            $this->errors[] = 'Payment mode must be a valid option.';
        }
        if (!empty(trim($nature)) && !$this->validateNature($nature)) {
            $this->errors[] = 'Nature of business must be a valid option.';
        }
        if (!\Zend_Validate::is($remark, 'Regex', $this->regexdesc) && strlen(trim($remark)) > 65535) {
            $this->errors[] = 'Remark must be valid and max 65535 characters are allowed.';
        }
    }

    /**
     * Process whole execution like validation, save, email, message
     * the bulkorder detail.
     * @return Null
     */
    public function customOrderRequest() {
        if (!empty($_REQUEST['subscription'])) {
            if (strpos($_REQUEST['subscription'], 'Thank') > -1 || strpos($_REQUEST['subscription'], 'confirmation')) {
                $this->messageManager->addSuccessMessage(__($_REQUEST['subscription']));
            } else {
                $this->messageManager->addErrorMessage(__($_REQUEST['subscription']));
            }
            unset($_REQUEST['subscription']);
        }
        $token = $this->getRequest()->getPost('csrf_token_post');
        if ( $token && isset($_SESSION['csrf_token']) && in_array($token, $_SESSION['csrf_token'])) {
            $to = 'skumar@urjakart.com';
            $toClient = $this->getRequest()->getPost('email');
            $bcc ='urjakart@pipedrivemail.com,info@urjakart.com,kathryn.tabitha@lakshyanet.com,saurabh.shrivastava@lakshyanet.com';
            $sub = 'Post Your Requirement Ref No - ';
            $from = 'no-reply@urjakart.com';
            $products = $this->getRequest()->getPost('productName');
            $qtys = $this->getRequest()->getPost('qty');
            $reason = $this->getRequest()->getPost('reason');
            $payment = $this->getRequest()->getPost('paymentMode');
            $name = $this->getRequest()->getPost('name');
            $phone = $this->getRequest()->getPost('mobile');
            $company = $this->getRequest()->getPost('company_name');
            $nature = $this->getRequest()->getPost('nature');
            $gst_no =  $this->getRequest()->getPost('gst_no');
            $remark = filter_var($this->getRequest()->getPost('remark'), FILTER_SANITIZE_STRING);
            $productData = array_combine($products, $qtys);
            $this->validateData($name, $toClient, $phone, $company, $productData, $reason, $payment, $nature, $gst_no, $remark);
            $file = $this->getRequest()->getFiles('sheet');
            $imageTmp = $file['tmp_name'];
            $strContent = '';
            $imageName = $this->docValidate($file);
            if ($imageTmp && $imageName && count($this->errors) == 0) {
                $strContent = chunk_split(base64_encode(file_get_contents($imageTmp)));
                $path = $this->mediaPath . 'upload/bulkorderimage/' . $imageName;
                try {
                    if (!copy($imageTmp,$path)) {
                        $this->errors[] = 'Unable to save sheet for custom order.';
                    }
                } catch (\Exception $ex) {
                    $this->errors[] = 'Unable to save sheet for custom order.';
                }
            }
            if ($this->errors) {
                foreach($this->errors as $error) {
                    $this->messageManager->addErrorMessage(__($error));
                }
                $this->removeToken($token);
                return;
            }
            $as = explode(" ", $name);
            $fName = $as[0];
            $ref_no = $this->saveData($sub, $name, $phone, $toClient, $company, $payment, $productData, $reason, $nature, $gst_no, $remark, $imageName);
            if ($ref_no['error']) {
                $this->messageManager->addErrorMessage(__($ref_no['error']));
                $this->removeToken($token);
                return;
            }
            $msgHeader = $this->getMsgHtml($ref_no['refno'], $name, $toClient, $phone, $company, $productData, $reason, $remark, $payment, $nature, $gst_no);
            $msgClient = $this->getClientMsgHtml($ref_no['refno'], $fName);
            if ($error = $this->sendMessage($fName, $phone, $ref_no['refno'])) {
                $this->messageManager->addErrorMessage(__($error));
            }
            $sub = $sub.$ref_no['refno'];
            $this->sendEmail($to, $toClient, $name, $sub, $msgHeader, $msgClient, $imageName, $strContent, $from, $bcc);
            $this->removeToken($token);
            return;
        }
    }
}
?>