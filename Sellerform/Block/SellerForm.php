<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Sellerform\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class SellerForm
 *
 * @package Urjakart\Sellerform\Block
 */
class SellerForm extends Template
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
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $mediaPath;

    /**
     * @var \Urjakart\Sellerform\Block\SellerForm
     */
    private $extension = ['png','jpg','jpeg','gif','doc','docx','txt','odt','pdf','ods','xls'];

    /**
     * @var \Urjakart\Sellerform\Block\SellerForm
     */
    private $regex = ["/^[a-zA-Z0-9\'._+,\"\/\- ]+$/"];

    /**
     * @var \Urjakart\Sellerform\Block\SellerForm
     */
    private $errors = [];

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * SellerForm constructor.
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
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
        $this->customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $this->fileSystem = $objectManager->get('Magento\Framework\Filesystem');
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
     * Get Seller Form Action url
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl(
            'sell-on-urjakart',
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
    private function getMsgHtml($ref_no, $name, $email, $phone, $company, $oem, $sell) {
        $style = 'style="text-align:left;width:100%;border-bottom:1px solid #ccc;font-size:12px;line-height:135%;color:#464647;border: 1px solid #1e439b;"';
        $dh = 'style="padding:10px;border:1px solid #ccc;border-bottom:0;border-right:0"';
        $dd = 'style="width:60%;padding:10px;border:1px solid #ccc;border-bottom:0;border-right:0;"';
        $msg = '<div style="width:700px;color:#fff; text-align:left; margin:0; font-family:Verdana,Arial,Helvetica,sans-serif">';
        $msg .= '<div style="color:#464647;margin-bottom: 22px;"><span style="text-transform:capitalize">' . $name . '</span> has submitted the seller form.</div>';
        $msg .= '<h2 style="background-color: #1e439b;color: #ffffff;border: 1px solid #1e439b;padding:8px 25px;display: inline-block;margin: 0;">Seller Form...</h2>';
        $msg .= '<table cellpadding="0" cellspacing="0" ' . $style . '>';
        $msg .= '<tr><td ' . $dh . '><b>Ref Number</b></td>';
        $msg .= '<td ' . $dd . '>' . strip_tags($ref_no) . '</td></tr>';
        $msg .= '<tr><td ' . $dh . ' style="border:1px solid #ccc;"><b>Email</b></td>';
        $msg .= '<td ' . $dd . '><a href="#" style="color:#15c; text-decoration:none">' . strip_tags($email) . '</a></td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Name</b></td>';
        $msg .= '<td ' . $dd . '>' . strip_tags($name) . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Mobile</b></td>';
        $msg .= '<td ' . $dd . '>' . strip_tags($phone) . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Company Name</b></td>';
        $msg .= '<td ' . $dd . '>' . strip_tags($company) . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>OEM/Distributor</b></td>';
        $msg .= '<td ' . $dd . '>' . strip_tags($oem) . '</td></tr>';
        $msg .= '<tr><td ' . $dh . '><b>Sell</b></td>';
        $msg .= '<td ' . $dd . '>' . strip_tags($sell) . '</td></tr>';
        $msg .= '</table></div>';

        return $msg;
    }

    /**
     * Get email template html for client/seller
     * @return string
     */
    private function getClientMsgHtml($ref_no, $name) {
        $style = 'style="width:700px;color:#fff;max-height:950px;text-align:left;font-family:Verdana,Arial,Helvetica,sans-serif;margin:0;line-height:25px;"';
        $msg = '<div ' . $style . '>';
        $msg .= '<div style="color:#464647;">';
        $msg .= '<span style="text-transform:capitalize;">Hi ' . $name .'</span>,<br><br>';
        $msg .= 'Thanks for registering as a seller with us. ';
        //$msg .= '<a href="' . $this->getBaseUrl() . '" > Urjakart</a>. ';
        $msg .= 'We are currently reviewing your catalog and price list. ';
        $msg .= 'We will get back to you shortly. ';
        $msg .= 'Please use ref# ' . $ref_no . ' for further communication.';
        $msg .= '</div></div>';
        $msg .= '<div style="font-size:12px;margin-top:20px;color:#464647; line-height:18px;">';
        $msg .= 'Regards,<br>Subham from Urjakart<br>+91-9599-304-982<br>skumar@urjakart.com</div>';

        return $msg;
    }

    /**
     * Save form data in database
     * @return array
     */
    private function saveData($sub, $name, $phone, $email, $company, $oem, $sell, $certificateName, $priceListName) {

        $return = ['refno' => '', 'error' => ''];
        try {
            $tableName = $this->connection->getTableName('form_seller');
            $query = 'select ref_no from ' . $tableName . ' order by id desc limit 1';
            $ref_count = $this->connection->fetchRow($query);
            if (isset($ref_count['ref_no']) && $ref_count['ref_no'] == 'FC005000') {
                $ref_count['ref_no'] = 'FC' . sprintf('%06d', rand(4000, 5000));
            }
            $ref_count = isset($ref_count['ref_no']) ? $ref_count['ref_no'] : 5000;
            $ref_count = explode('C', $ref_count);
            $ref_count = (int)(end($ref_count)) + 1;
            $ref_no = 'FC' . sprintf('%06d', $ref_count);
            $data = [
                'ftitle'             => $sub,
                'name'               => $name,
                'phone_no'           => $phone,
                'email'              => $email,
                'company_name'       => $company,
                'distributor'        => $oem,
                'sell_other_web'     => $sell,
                'certificate_name'   => $certificateName,
                'pricelist_doc_name' => $priceListName,
                'ref_no'             => $ref_no
            ];
            $this->connection->insert($tableName, $data);
            $this->connection->closeConnection();
            $return['refno'] = $ref_no;
        } catch (\Exception $ex) {
            $return['error'] = 'Unknown error, please contact on +91-9015-938-938';
        }

        return $return;
    }

    /**
     * Send Email to both urjakart and client
     */
    private function sendEmail($to, $toClient, $name, $sub, $msg, $msgClient, $from, $bcc, $certificateName, $strContentCert, $priceListName, $strContentPl) {

        if (($certificateName && $strContentCert) || ($priceListName && $strContentPl)) {
            $strSid = md5(uniqid(time()));
            $Header  = "From: ".$name." <".$from.">".PHP_EOL;
            $Header .="Reply-To: " . $toClient . PHP_EOL;
            $Header .= 'Bcc:' .$bcc."".PHP_EOL;
            $Header .= "MIME-Version: 1.0".PHP_EOL;
            $Header .= "Content-Type: multipart/mixed; boundary=\"".$strSid."\"".PHP_EOL . PHP_EOL;
            $strHeader = "--" . $strSid . PHP_EOL;
            $strHeader .= "Content-type: text/html; charset=utf-8".PHP_EOL;
            $strHeader .= "Content-Transfer-Encoding:7bit".PHP_EOL .PHP_EOL;
            $strHeader .= $msg . PHP_EOL;
            if ($certificateName && $strContentCert) {
                $strHeader .= "--" . $strSid . PHP_EOL;
                $strHeader .= "Content-Type: application/octet-stream; name=\"" . $certificateName . "\"" . PHP_EOL;
                $strHeader .= "Content-Transfer-Encoding: base64" . PHP_EOL;
                $strHeader .= "Content-Disposition: attachment; filename=\"" . $certificateName . "\"" . PHP_EOL . PHP_EOL;
                $strHeader .= $strContentCert . "" . PHP_EOL;
            }
            if ($priceListName && $strContentPl) {
                $strHeader .= "--" . $strSid . PHP_EOL;
                $strHeader .= "Content-Type: application/octet-stream; name=\"" . $priceListName . "\"" . PHP_EOL;
                $strHeader .= "Content-Transfer-Encoding: base64" . PHP_EOL;
                $strHeader .= "Content-Disposition: attachment; filename=\"" . $priceListName . "\"" . PHP_EOL . PHP_EOL;
                $strHeader .= $strContentPl . "" . PHP_EOL;
            }
        } else {
            $Header = 'MIME-Version: 1.0' . "\r\n" .
                'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                'From: '.$name.' <'.$from.'>'. "\r\n" .
                'Reply-To: ' . $toClient . "\r\n" .
                'Bcc:' .$bcc.''. "\r\n".
                'X-Mailer: PHP/' . phpversion(); " \r\n";
            $strHeader = $msg . PHP_EOL;
        }

        $headers = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
            'From: ' .$name."<".$to.">\r\n" .
            'Reply-To: ' . $to . "\r\n" .
            'X-Mailer: PHP/' . phpversion(); " \r\n";

        try {
            $mail_us = mail($to, $sub, $strHeader, $Header);
            $mail_client = mail($toClient, $sub, $msgClient, $headers);
            if ( $mail_us && $mail_client ) {
                $this->messageManager->addSuccessMessage(__('Thanks for taking time to fill out the seller registration form. Our Vendor relations team will be in touch with you shortly!'));
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
     * Validate document of seller
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
                $this->errors[] = 'File must be .png,.jpeg,.jpg,.gif,.doc,.docx,.txt,.pdf,.xls only allowed';
            }
            $docName = $img1 . '_' . uniqid(time());
            $docName .= '.' . $img2;
        }
        if ($docError && $docErrorName) {
            $this->errors[] = 'invalid file ' . $docErrorName . ' or unexpected error!';
        }

        return $docName;
    }

    /**
     * Validate seller with other or not
     * @return boolean
     */
    private function validateSell($sell) {
        $sellTypes = [
            'yes',
            'no'
        ];
        if (in_array(strtolower(trim($sell)), $sellTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Validate seller form data
     * @return array
     */
    public function validateData($name, $email, $phone, $company, $oem, $sell) {

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
        if (!(\Zend_Validate::is($email, 'NotEmpty') &&
            \Zend_Validate::is($email, 'EmailAddress') &&
            strlen(trim($email)) <= 40)
        ) {
            $this->errors[] = 'Email must be in valid format and max 40 characters are allowed.';
        }
        if (!(\Zend_Validate::is($company, 'NotEmpty') &&
            \Zend_Validate::is($company, 'Regex', $this->regex) &&
            strlen(trim($company)) <= 50)
        ) {
            $this->errors[] = 'Company name must be valid and max 50 characters are allowed.';
        }
        if (!(\Zend_Validate::is($oem, 'NotEmpty') &&
            \Zend_Validate::is($oem, 'Regex', $this->regex) &&
            strlen(trim($oem)) <= 50)
        ) {
            $this->errors[] = 'Brand name must be valid and max 50 characters are allowed.';
        }
        if (!$this->validateSell($sell)) {
            $this->errors[] = 'Select Yes, if you sell on other website.';
        }
    }

    /**
     * Process whole execution like validation, save, email, message
     * the seller detail.
     * @return Null
     */
    public function becomeSeller() {
        $token = $this->getRequest()->getPost('csrf_token_post');
        if ($token && isset($_SESSION['csrf_token']) && in_array($token, $_SESSION['csrf_token'])) {
            $to = 'skumar@urjakart.com';
            $toClient = $this->getRequest()->getPost('email');
            $from = 'no-reply@urjakart.com';
            $bcc ='info@urjakart.com,kathryn.tabitha@lakshyanet.com,saurabh.shrivastava@lakshyanet.com';
            $sub = 'Become Urjakart Seller';
            $name = $this->getRequest()->getPost('name');
            $phone = $this->getRequest()->getPost('mobile');
            $company = $this->getRequest()->getPost('company_name');
            $oem = $this->getRequest()->getPost('oem');
            $sell = $this->getRequest()->getPost('sell');
            $this->validateData($name, $toClient, $phone, $company, $oem, $sell);
            $certificate = $this->getRequest()->getFiles('Field11');
            $certificateTmp = $certificate['tmp_name'];
            $strContentCert = '';
            $certificateName = $this->docValidate($certificate);
            if ($certificateTmp && $certificateName && count($this->errors) == 0) {
                $strContentCert = chunk_split(base64_encode(file_get_contents($certificateTmp)));
                $path = $this->mediaPath . 'upload/sellercertificate/' . $certificateName;
                try {
                    if (!copy($certificateTmp,$path)) {
                        $this->errors[] = 'Unable to save seller certificate!';
                    }
                } catch (\Exception $ex) {
                    $this->errors[] = 'Unable to save seller certificate!';
                }
            }
            $priceList = $this->getRequest()->getFiles('Field12');
            $priceListTmp = $priceList['tmp_name'];
            $strContentPl = '';
            $priceListName = $this->docValidate($priceList);
            if ($priceListTmp && $priceListName && count($this->errors) == 0) {
                $strContentPl = chunk_split(base64_encode(file_get_contents($priceListTmp)));
                $path = $this->mediaPath . 'upload/sellerpricelist/' . $priceListName;
                try {
                    if (!copy($priceListTmp,$path)) {
                        $this->errors[] = 'Unable to save seller price list doc!';
                    }
                } catch (\Exception $ex) {
                    $this->errors[] = 'Unable to save seller price list doc!';
                }
            }
            if ($this->errors) {
                foreach($this->errors as $error) {
                    $this->messageManager->addErrorMessage(__($error));
                }
                $this->removeToken($token);
                return;
            }
            $ref_no = $this->saveData($sub, $name, $phone, $toClient, $company, $oem, $sell, $certificateName, $priceListName);
            if ($ref_no['error']) {
                $this->messageManager->addErrorMessage(__($ref_no['error']));
                $this->removeToken($token);
                return;
            }
            $msg = $this->getMsgHtml($ref_no['refno'], $name, $toClient, $phone, $company, $oem, $sell);
            $msgClient = $this->getClientMsgHtml($ref_no['refno'], $name);
            if ($error = $this->sendMessage($name, $phone, $ref_no['refno'])) {
                $this->messageManager->addErrorMessage(__($error));
            }
            $this->sendEmail($to, $toClient, $name, $sub, $msg, $msgClient, $from, $bcc, $certificateName, $strContentCert, $priceListName, $strContentPl);
            $this->removeToken($token);

            return;
        }
    }

    /**
     * Retrieve copyright information
     *
     * @return string
     */
    public function getCopyright()
    {

        return   $this->_scopeConfig->getValue(
                'design/footer/copyright',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
}