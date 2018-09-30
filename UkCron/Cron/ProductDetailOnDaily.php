<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\UkCron\Cron;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;

/**
 * Class ProductDetailOnDaily
 *
 * @package Urjakart\UkCron\Cron
 */
class ProductDetailOnDaily
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $connection;

    /**
     * CSV Processor
     *
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $fileSystem;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $mediaPath;

    /**
     * @param \Magento\Framework\File\Csv $csvProcessor
     */
    public function __construct(
        Csv $csvProcessor
    )
    {
        $this->csvProcessor = $csvProcessor;
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $this->fileSystem = $objectManager->get('\Magento\Framework\Filesystem');
        $this->mediaPath  = $this->fileSystem->getDirectoryWrite(DirectoryList::PUB)->getAbsolutePath();
    }

    /**
     * @description This cron method executing daily to check
     * the detail of new product addition in catalog and send
     * and email to catalog team.
     */
    public function exportProducts()
    {
        try {
            $pdp = $this->connection->getTableName('pipedrive_product');
            $cpev = $this->connection->getTableName('catalog_product_entity_varchar');
            $cpip = $this->connection->getTableName('catalog_product_index_price');
            $tc = $this->connection->getTableName('tax_class');
            date_default_timezone_set("Asia/Kolkata");
            $last = date("Y-m-d H:i:s", strtotime(date("Y-m-d") .' -1 day'));

            $finalData = [
                [
                    'product_id' => 'ProductId',
                    'sku' => 'Sku',
                    'name' => 'Name',
                    'price' => 'Price',
                    'special_price' => 'SpecialPrice',
                    'cost' => 'Cost',
                    'hsn_no' => 'HsnNo',
                    'tax_class_id' => 'TaxClassId'

                ]
            ];
            $query = 'select distinct pdp.product_id,pdp.sku,pdp.name,pdp.price,pdp.special_price, pdp.cost,';
            $query .= 'cpev.value as hsn_no, tc.class_name from ' . $pdp;
            $query .= ' pdp inner join ' . $cpev . ' cpev on pdp.product_id = cpev.entity_id inner';
            $query .= ' join ' .$cpip . ' cpip on pdp.product_id = cpip.entity_id';
            $query .= ' inner join ' . $tc . ' tc on cpip.tax_class_id = tc.class_id where';
            //$query .= ' pdp.product_id=41516 and cpev.attribute_id=147';
            $query .= ' cpev.attribute_id=147 and pdp.date > "' . $last . '"';
            $statement = $this->connection->prepare($query);
            $statement->execute();
            $data = $statement->fetchAll();
            $csvData = array_merge($finalData, $data);
            $this->csvProcessor->saveData($this->mediaPath . 'product.csv', $csvData);
            $this->sendEmailWithCsv();

        } catch (\Exception $e) {
            $to = "ashishg@urjakart.com";
            $subject = "Exception In Product Csv Export";
            $message = '<div style="color:red;font-size:20px;padding:30px;border:2px solid #4285f4">';
            $message .= $e->getMessage() . '</div>';
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Exception<exception@urjakart.com>' . "\r\n";
            @mail($to,$subject,$message,$headers);
        }
    }

    /**
     * @description send an email with product detail csv file.
     * @return boolean true/false
     * @throws \Exception
     */
    private function sendEmailWithCsv() {

        try {
            $to = 'ankits@urjakart.com,anurags@urjakart.com,mohitc@urjakart.com';
            //$to = 'ashishg@urjakart.com';
            $from = 'automated@urjakart.com';
            $fromName = 'Urjakart';
            $subject = 'Product Data: ' . date("Y-m-d", strtotime(date("Y-m-d H:i:s") .' -1 day'));
            $htmlContent = '<h1>Product detail csv file from Urjakart</h1>';
            $htmlContent .= '<p>This email has csv file with product detail for Catalog Team.</p>';
            $headers = "From: $fromName"." <".$from.">";
            $semi_rand = md5(time());
            $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
            $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
            $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
                "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";
            $file = $this->mediaPath . 'product.csv';
            if (!empty($file) > 0) {
                if (is_file($file)) {
                    $message .= "--{$mime_boundary}\n";
                    $fp = @fopen($file,"rb");
                    $data = @fread($fp,filesize($file));
                    @fclose($fp);
                    $data = chunk_split(base64_encode($data));
                    $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" .
                        "Content-Description: ".basename($file)."\n" .
                        "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" .
                        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                }
            }
            $message .= "--{$mime_boundary}--";
            $returnPath = "-f" . $from;
            $mail = @mail($to, $subject, $message, $headers, $returnPath);

            return $mail;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
?>
