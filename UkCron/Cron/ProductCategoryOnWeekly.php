<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\UkCron\Cron;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Framework\Archive\Zip;

/**
 * Class ProductCategoryOnWeekly
 *
 * @package Urjakart\UkCron\Cron
 */
class ProductCategoryOnWeekly
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
     * Zip Processor
     *
     * @var \Magento\Framework\Archive\Zip
     */
    protected $zipProcessor;

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
     * @param \Magento\Framework\Archive\Zip $zipProcessor
     */
    public function __construct(
        Csv $csvProcessor,
        Zip $zipProcessor
    )
    {
        $this->csvProcessor = $csvProcessor;
        $this->zipProcessor = $zipProcessor;
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $this->fileSystem = $objectManager->get('\Magento\Framework\Filesystem');
        $this->mediaPath  = $this->fileSystem->getDirectoryWrite(DirectoryList::PUB)->getAbsolutePath();
    }

    /**
     * @description This cron method executing weekly to check
     * the detail of product modification in catalog and send
     * and email to catalog team.
     */
    public function exportProductCategories()
    {
        try {
            $cpe = $this->connection->getTableName('catalog_product_entity');
            $cpei = $this->connection->getTableName('catalog_product_entity_int');
            $csi = $this->connection->getTableName('cataloginventory_stock_item');
            $cpie = $this->connection->getTableName('catalog_product_index_eav');
            $eaov = $this->connection->getTableName('eav_attribute_option_value');
            $ccp = $this->connection->getTableName('catalog_category_product');
            $cce = $this->connection->getTableName('catalog_category_entity');
            $ccev = $this->connection->getTableName('catalog_category_entity_varchar');
            $cpev = $this->connection->getTableName('catalog_product_entity_varchar');
            $cpip = $this->connection->getTableName('catalog_product_index_price');

            $csvData = [
                [
                    'sku' => 'Sku',
                    'brand' => 'Brand',
                    'catl1' => 'categoryL1',
                    'catl2' => 'categoryL2',
                    'catl3' => 'categoryL3',
                    'catl4' => 'categoryL4',
                    'mrp' => 'MRP',
                    'sp' => 'SP',
                    'status' => 'Status',
                    'stock' => 'Stock'
                ]
            ];
            $query = 'select distinct cpe.sku, eaov.value as brand,';
            $query .= ' case when cce.level=2 then ccev.value end as catl1,';
            $query .= ' case when cce.level=3 then ccev.value end as catl2,';
            $query .= ' case when cce.level=4 then ccev.value end as catl3,';
            $query .= ' case when cce.level=5 then ccev.value end as catl4,';
            $query .= ' cpip.price as mrp, cpip.final_price as sp,';
            $query .= ' IF(cpei.value=1, "Enabled", "Disabled") as status,';
            $query .= ' IF(csi.is_in_stock=0 or csi.is_in_stock=1 and cpev.value=0, "In Stock", "Out Of Stock") as stock';
            $query .= ' from ' . $cpe . ' cpe';
            $query .= ' inner join ' . $cpei . ' cpei on (cpei.entity_id=cpe.entity_id and cpei.attribute_id=97 and cpei.store_id=0)';
            $query .= ' inner join ' . $csi . ' csi on csi.product_id = cpe.entity_id';
            $query .= ' inner join ' . $cpie . ' cpie on cpe.entity_id=cpie.entity_id';
            $query .= ' inner join ' . $eaov . ' eaov on cpie.value=eaov.option_id';
            $query .= ' inner join ' . $ccp . ' ccp on ccp.product_id=cpe.entity_id';
            $query .= ' inner join ' . $cce . ' cce on ccp.category_id=cce.entity_id';
            $query .= ' inner join ' . $ccev . ' ccev on (ccev.entity_id=ccp.category_id and ccev.attribute_id=45 and ccev.store_id=0)';
            $query .= ' inner join ' . $cpev . ' cpev on (cpev.entity_id=cpe.entity_id and cpev.attribute_id=138)';
            $query .= ' inner join ' . $cpip . ' cpip on cpip.entity_id=cpe.entity_id';
            $query .= ' where cpie.attribute_id=83 and cce.level != 1';
            //$query .= ' where cpe.entity_id=29880 and cpie.attribute_id=83 and cce.level != 1';
            echo $query; die;
            $statement = $this->connection->prepare($query);
            $statement->execute();
            $data = $statement->fetchAll();
            $sku = '';
            $arr = [
                'sku' => '',
                'brand' => '',
                'catl1' => '',
                'catl2' => '',
                'catl3' => '',
                'catl4' => '',
                'mrp' => '',
                'sp' => '',
                'status' => '',
                'stock' => ''
            ];
            $backup = $arr;
            foreach ($data as $product) {
                if ($sku == '') {
                    $sku = $product['sku'];
                } elseif ($sku != $product['sku']) {
                    $sku = $product['sku'];
                    array_push($csvData, $arr);
                    $arr = $backup;
                }

                if ($sku == $product['sku']) {
                    $arr['sku'] = $product['sku'];
                    $arr['brand'] = $product['brand'];
                    if ($product['catl1']) {
                        if ($arr['catl1'])
                          $arr['catl1'] .= ',' . $product['catl1'];
                        else
                          $arr['catl1'] = $product['catl1'];
                    }
                    if ($product['catl2']) {
                        if ($arr['catl2'])
                          $arr['catl2'] .= ',' . $product['catl2'];
                        else
                          $arr['catl2'] = $product['catl2'];
                    }
                    if ($product['catl3']) {
                        if ($arr['catl3'])
                          $arr['catl3'] .= ',' . $product['catl3'];
                        else
                          $arr['catl3'] = $product['catl3'];
                    }
                    if ($product['catl4']) {
                        if ($arr['catl4'])
                          $arr['catl4'] .= ',' . $product['catl4'];
                        else
                          $arr['catl4'] = $product['catl4'];
                    }
                    $arr['mrp'] = $product['mrp'];
                    $arr['sp'] = $product['sp'];
                    $arr['status'] = $product['status'];
                    $arr['stock'] = $product['stock'];
                }
            }
            array_push($csvData, $arr);
            $this->csvProcessor->saveData($this->mediaPath . 'category.csv', $csvData);
            $this->zipProcessor->pack($this->mediaPath . 'category.csv', $this->mediaPath . 'category.zip');
            $this->sendEmailWithCsv();

        } catch (\Exception $e) {
            $to = "ashishg@urjakart.com";
            $subject = "Exception In Product Category Csv Export";
            $message = '<div style="color:red;font-size:20px;padding:30px;border:2px solid #4285f4">';
            $message .= $e->getMessage() . '</div>';
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Exception<exception@urjakart.com>' . "\r\n";
            @mail($to,$subject,$message,$headers);
        }
    }

    /**
     * @description send an email with product category detail csv file.
     * @return boolean true/false
     * @throws \Exception
     */
    private function sendEmailWithCsv() {

        try {
            date_default_timezone_set("Asia/Kolkata");
            $to = 'ankits@urjakart.com,anurags@urjakart.com,mohitc@urjakart.com,dheerajk@urjakart.com,akashv@urjakart.com,sandeep@urjakart.com';
            //$to = 'ashishg@urjakart.com';
            $from = 'automated@urjakart.com';
            $fromName = 'Urjakart';
            $subject = 'Product Category Data: ' . date("Y-m-d");
            $htmlContent = '<h1>Product Category detail csv file from Urjakart</h1>';
            $htmlContent .= '<p>This email has csv file with product detail for Catalog Team.</p>';
            $headers = "From: $fromName"." <".$from.">";
            $semi_rand = md5(time());
            $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
            $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
            $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
                "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";
            $file = $this->mediaPath . 'category.zip';
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
