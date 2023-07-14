<?php
/**
 * Magehqm2
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the magehq.com license that is
 * available through the world-wide-web at this URL:
 * https://magehq.com/license.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   magehqm2
 * @package    Magehqm2_Core
 * @copyright  Copyright (c) 2022 magehqm2 (https://magehq.com/)
 * @license    https://magehq.com/license.html
 */

namespace Magehqm2\Core\Block\Adminhtml\System;
use Magento\Framework\App\Filesystem\DirectoryList;

class ListLicense extends \Magento\Config\Block\System\Config\Form\Field
{

    const SITE_URL      = 'https://magehq.com';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    protected $_key_path;

    private $_list_files = [];

    /**
     * [__construct description]
     * @param \Magento\Backend\Block\Template\Context              $context 
     * @param \Magento\Framework\App\ResourceConnection            $resource      
     * @param \Magehqm2\Core\Helper\Data                                 $helper        
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress 
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magehqm2\Core\Helper\Data $helper,
        \Magehqm2\Core\Model\License $license,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
        )
    {
        parent::__construct($context);
        $this->_resource      = $resource;
        $this->_helper        = $helper;
        $this->_remoteAddress = $remoteAddress;
        $this->_license       = $license;
         $this->_curl = $curl;
    }

    public function getListLicenseFiles() {
        if(!$this->_list_files) {
            $path = $this->_filesystem->getDirectoryRead(DirectoryList::APP)->getAbsolutePath('code/Magehqm2/');
            $files = glob($path . '*/*/license.xml');
           

            if(is_array($files) && $files) {
                $this->_list_files = array_merge($this->_list_files, $files);
            }
          
        }
        return $this->_list_files;
    }
    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $files = $this->getListLicenseFiles();
       
        $email = $html = '';
        $list_products = $this->getProductList();

        $products = $list_products?$list_products:[];
        $extensions = [];
        foreach ($files as $file) {
            $xmlObj = new \Magento\Framework\Simplexml\Config($file);
            $xmlData = $xmlObj->getNode();
            $sku = $xmlData->code;

            $name = $xmlData->name;
            if($email=='' && (string)($xmlData->email)){
                $email = $xmlData->email;
            }

            if($products){
                foreach($products as $_product){
                    if(is_array($_product) && $sku == $_product['sku']){
                        $_product['extension_name'] = (string)$name;
                        $_product['purl'] = $xmlData->item_url;
                        $_product['item_title']     = $xmlData->item_title;
                        $_product['version']        = $xmlData->version;
                        $extensions[] = $_product;
                        break;
                    }
                }
            }else {
                $_product = [];
                $_product['extension_name'] = (string)$name;
                $_product['purl']           = $xmlData->item_url;
                $_product['item_title']     = $xmlData->item_title;
                $_product['version']        = $xmlData->version;
                $_product['sku']            = $sku;
                $_product['key']            = ($xmlData->key)?$xmlData->key:'';
                $_product['pimg']           = ($xmlData->pimg)?$xmlData->pimg:'';
                $extensions[] = $_product;
            }
        }
        if ($email) {
            throw new \RuntimeException(__('Something went wrong while validating license. Please contact %1', $email));
        }

        if(!empty($extensions)){
            $connection = $this->_resource->getConnection();
            $html .= '<h1 class="magehqm2-tt">MAGEHQ Licenses</h1>';
            $html .= '<div class="magehqm2-license">';

            foreach ($extensions as $_extension) {
                $name = str_replace('[licenses]', '[' . str_replace(['-','_',' '], [''], $_extension['sku']) . ']', $element->getName());
                $value = $this->_helper->getConfig('general/' . str_replace(['-','_',' '], [''], $_extension['sku']),'magehqm2license',null);

                if(!$value && isset($_extension['key_license']) && $_extension['key_license']){
                    $value = $_extension['key_license'];
                }
                if($value) {
                    $value = trim($value);
                }
               
                $baseUrl = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_WEB,
                    $this->_storeManager->getStore()->isCurrentlySecure()
                    );
                $remoteAddress = $this->_remoteAddress->getRemoteAddress();
                $domain        = $this->getDomain($baseUrl);

                $response = $this->verifyLicense($value,$_extension['sku'], $domain, $remoteAddress);

                $license = isset($response["key_license"])?$response["key_license"]:false;
                if (!is_array($license) && isset($response['status']) && $response['status'] != 1) {
                    $license = [];
                    $license['is_valid'] = 0;
                }
                if (isset($response['status']) &&  $response['status'] == 1) {
                    $license = [];
                    $license['is_valid'] = 1;
                }

                $html .= '<div class="magehqm2-vitem">';
                $html .= '<div class="magehqm2-img">';
                $html .= '<a href="' . $_extension['purl'] . '" target="_blank" title="' . $_extension['name'] . '"><img src="' .  $_extension['pimg'] . '"/></a>';
                $html .= '</div>';
                $html .= '<div class="pdetails">';
                $html .=  '<p class="pd-name"><a href="' . $_extension['purl'] . '" target="_blank" title="' . $_extension['name'] . '">' . str_replace(' for Magento 2', '', $_extension['name']) . '</a></p>';
                $html .= '<div class="pd-license">';
                $html .= '<span class="plicense"><strong>License Serial</strong></span>';
                $html .= '<div><input type="text" name="' . $name . '" value="' . $value . '"/></div>';
                $html .= '<div class="pmeta">';
                if(!empty($license) && $license['is_valid']){
                    $html .= '<p><strong>Status: </strong><span class="pvalid">Valid</span></p>';
                }else{
                    $html .= '<p><strong>Status: </strong><span class="pinvalid">Invalid</span></p>';
                }
                if(!empty($license) && isset($license['description'])){
                    $html .= $license['description'];
                }
                if(!empty($license) && isset($license['created_at'])){
                    $html .= '<p><strong>Activation Date:</strong> ' . $license['created_at'] . '</p>';
                }
                if(!empty($license) && isset($license['expired_time'])){
                    $html .= '<p><strong>Expiration Date:</strong> ' . $license['expired_time'] . '</p>';
                }
                $html .= '</div>';
                $licenseCollection = $this->_license->getCollection();
                foreach ($licenseCollection as $klience => $vlience) {
                    if($vlience->getData('extension_code') == $_extension['sku']){
                        $vlience->delete();
                    }
                }
                $licenseData = [];
                if(isset($_extension['sku'])){
                    $licenseData['extension_code'] = $_extension['sku'];
                }
                if(isset($_extension['name'])){
                    $licenseData['extension_name'] = $_extension['name'];
                }
                if(empty($license) || !$license['is_valid']){
                    $licenseData['status'] = 0;
                }else{
                    $licenseData['status'] = 1;
                }
                $this->_license->setData($licenseData)->save();
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }else{
            $licenseCollection = $this->_license->getCollection();
            foreach ($licenseCollection as $klience => $vlience) {
                $vlience->delete();
            }
        }
        return $this->_decorateRowHtml($element, $html);
    }
    public function getProductList() {
        try{
            //Authentication rest API magento2, get access token
            $url = self::getListUrl();
           
            $direct_url = $url;
           $response = @file_get_contents($direct_url);
            if(!$response) {
                 $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => $url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_SSL_VERIFYPEER=>false,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);

                if ($response) {
                }
                else {
                    $response = @file_get_contents($url);
                    if(!$response) {
                        echo 'An error has occurred: ' . curl_error($crl);
                        return[];
                    }
                }
                curl_close($curl);
            }
            return json_decode($response, true);
        } catch(\Exception $e) {

        }
        return [];
    }

    public function verifyLicense($license_key, $extension, $domain, $ip) {
        try{
            //Authentication rest API magento2, get access token
            $url = self::getVerifyUrl();
            $direct_url = $url."?license_key=".$license_key."&extension=".$extension.'&domain='.$domain.'&ip='.$ip;
            //$response = @file_get_contents($direct_url);
            
            //if(!$response) {

                // $key_path = $this->getKeyPath();
                $data = array("license_key"=>$license_key,"extension"=>$extension,"domain"=>$domain,"ip"=>$ip);
                // $crl = curl_init();
                // curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, TRUE);
                // curl_setopt($crl, CURLOPT_CAPATH, $key_path);
                // curl_setopt($crl, CURLOPT_SSL_VERIFYHOST, 2);
                // curl_setopt($crl, CURLOPT_URL, $url);
                // curl_setopt($crl, CURLOPT_FOLLOWLOCATION, TRUE);
                // curl_setopt($crl, CURLOPT_CUSTOMREQUEST, 'GET');
                //curl_setopt($crl, CURLOPT_POSTFIELDS, $data);

                // $response = curl_exec($crl);
                
                $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => $direct_url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_SSL_VERIFYPEER=>false,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);
                curl_close($curl);
             
                if ($response && isset(json_decode($response, true)[0])) {
                    return json_decode($response, true)[0];
                }
                else {
                    $url .="?license_key=".$license_key."&extension=".$extension."&domain=".$domain."&ip=".$ip;
                    $response = @file_get_contents($url);
                    if(!$response) {
                        echo 'An error has occurred: ' . curl_error($crl);
                        return[];
                    }
                }
            //}
            return json_decode($response, true);
        } catch(\Exception $e) {

        }
        return [];
    }
    public static function getListUrl() {
        $url = ListLicense::SITE_URL;
        return $url."/rest/V1/license/listproducts";
    }
    public static function getVerifyUrl() {
        $url = ListLicense::SITE_URL;
        return $url."/rest/V1/license/verify";
    }
    public function getKeyPath(){
        if(!$this->_key_path){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
            $base_url = $directory->getRoot();
            $this->_key_path = $base_url."/magehqm2license/cacert.pem";
        }
        return $this->_key_path;
    }
    public function getDomain($domain) {
        $domain = strtolower($domain);
        $domain = str_replace(['www.','WWW.','https://','http://','https','http'], [''], $domain);
        if($this->endsWith($domain, '/')){
            $domain = substr_replace($domain ,"",-1);
        }
        return $domain;
    }
    public function endsWith($haystack, $needle) {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
}