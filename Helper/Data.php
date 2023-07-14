<?php
/**
 * Magehqm2
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magehqm2.com license that is
 * available through the world-wide-web at this URL:
 * https://magehq.com/license.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Magehqm2
 * @package    Magehqm2_Seo
 * @copyright  Copyright (c) 2022 Magehqm2 (https://magehq.com/)
 * @license    https://magehq.com/license.html
 */

namespace Magehqm2\Core\Helper;

use \Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Default charset
     */
    const ICONV_CHARSET = 'UTF-8';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $cmsPage;

    protected $_license;

    protected $_moduleReader;
     /**
     * @var null|SerializerInterface
     */
    private $serializer;

    /**
     * @var Unserialize
     */
    private $unserialize;


    /**
     * @param \Magento\Framework\App\Helper\Context             $context        
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager   
     * @param \Magento\Cms\Model\Template\FilterProvider        $filterProvider 
     * @param \Magento\Framework\Registry                       $registry       
     * @param \Magento\Cms\Model\Page                           $cmsPage           
     * @param \Magehqm2\Core\Framework\Serialize\Serializer\Json $serializer     
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magehqm2\Core\Model\License $license,
        \Magento\Cms\Model\Page $cmsPage,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Unserialize\Unserialize $unserialize

    ) {
        parent::__construct($context);
        $this->_storeManager  = $storeManager;
        $this->filterProvider = $filterProvider;
        $this->registry       = $registry;
        $this->cmsPage        = $cmsPage;
        $this->_license        = $license;
        $this->_remoteAddress = $context->getRemoteAddress();
        $this->_moduleReader  = $moduleReader;
        $this->unserialize = $unserialize;
        $this->serializer = $serializer;
    }


    /**
     * Return brand config value by key and store
     *
     * @param string $key
     * @param \Magento\Store\Model\Store|int|string $store
     * @return string|null
     */
     public function getConfig($key, $group = "magehqm2core/general", $store = null)
     {
        $store     = $this->_storeManager->getStore($store);
        $websiteId = $store->getWebsiteId();

        if ($this->_storeManager->isSingleStoreMode()) {
            $result = $this->scopeConfig->getValue(
                $group . '/' .$key,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
                );
        } else {
            $result = $this->scopeConfig->getValue(
                $group . '/' .$key,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store);

        }
        if(!$result){
            $result = $this->scopeConfig->getValue(
                $group . '/' .$key,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null);
        }
        if(!$result){
            $result = $this->scopeConfig->getValue(
                $group . '/' .$key);
        }

        return $result;
    }
    
    
    public function serialize($value)
    {
        try {
            if ($this->serializer !== null) {
                return $this->serializer->serialize($value);
            }

            return '{}';
        } catch (\Exception $e) {
            return '{}';
        }
    }

    public function unserialize($value)
    {
        if (false === $value || null === $value || '' === $value) {
            return false;
        }

        if ($this->serializer === null) {
            return $this->unserialize->unserialize($value);
        }

        try {
            return $this->serializer->unserialize($value);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }

    public function filter($str)
    {
        if (!$str) return;
        $str       = $this->decodeDirectiveImages($str);
        $storeId   = $this->_storeManager->getStore()->getId();
        $filter    = $this->filterProvider->getBlockFilter()->setStoreId($storeId);
        $variables = [];
        if ($this->cmsPage->getId()) $variables['page'] = $this->cmsPage;
        if ($category = $this->getCurrentCategory()) $variables['category'] = $category;
        if ($product = $this->getCurrentProduct()) $variables['product'] = $product;
        $filter->setVariables($variables);
        $productMetadata = ObjectManager::getInstance()->get('Magento\Framework\App\ProductMetadataInterface');
        if ($productMetadata->getVersion() >= '2.4.0') {
            $filter->setStrictMode(false);
        }
        return $filter->filter($str);
    }

    /**
     * @return \Magento\Catalog\Model\Category
     */
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @param  string $content
     * @return string         
     */
    public function decodeDirectiveImages($content) {
        $matches = $search = $replace = [];
        preg_match_all( '/<img[\s\r\n]+.*?>/is', $content, $matches );
        foreach ($matches[0] as $imgHTML) {
            $key = 'directive/___directive/';
            if (strpos($imgHTML, $key) !== false) {
                $srcKey = 'src="';
                $start  = strpos($imgHTML, $srcKey) + strlen($srcKey);
                $end    = strpos($imgHTML, '"', $start);
                if ($end > $start) {
                    $imgSrc      = substr($imgHTML, $start, $end - $start);
                    $start       = strpos($imgSrc, $key) + strlen($key);
                    $imgBase64   = substr($imgSrc, $start);
                    $replaceHTML = str_replace($imgSrc, $this->urlDecoder->decode(urldecode($imgBase64)), $imgHTML);
                    $search[]    = $imgHTML;
                    $replace[]   = $replaceHTML;
                }
            }
        }
        return str_replace( $search, $replace, $content );
    }


    /**
     * @param  string  $string
     * @return boolean
     */
    public function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    /**
     * @return string
     */
    public function getMediaUrl()
    {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        return $mediaUrl;
    }

    /**
     * Remove base url
     */
    public function convertImageUrl($string)
    {
        $mediaUrl = $this->getMediaUrl();
        return str_replace($mediaUrl, '', $string);
    }

    /**
     * @return boolean
     */
    public function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * @return boolean
     */
    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 ||
        (substr($haystack, -$length) === $needle);
    }

    /**
     * @param  string $string
     * @return string         
     */
    public function getImageUrl($string)
    {
        if ($string && is_string($string) && strpos($string, 'http') === false && (strpos($string, '<div') === false)) {
            $mediaUrl = $this->getMediaUrl();
            $string   = $mediaUrl . $string;
        }
        return $string;
    }

    /**
     * Convert string to numbder
     */
    public function dataPreprocessing($data)
    {
        if (is_array($data)) {
            foreach ($data as &$_row) {
                $_row = $this->unserialize($_row);
                if ($_row === '1' || $_row === '0') {
                    $_row = (int) $_row;
                }
                $_row = $this->getImageUrl($_row);
                if (is_array($_row)) {
                    $_row = $this->dataPreprocessing($_row);
                }
            }
        }
        return $data;
    }

    /**
     * Pass through to mb_substr()
     *
     * @param string $string
     * @param int $offset
     * @param int $length
     * @return string
     */
    public function substr($string, $length, $keepWords = true)
    {
        $string = $this->cleanString($string);
        if ($keepWords) {
            if (preg_match('/^.{1,' . $length . '}\b/s', $string, $match)) {
                $string = $match[0];
            }
        } else {
            $string = mb_substr($string, 0, $length, self::ICONV_CHARSET);
        }
        return $string;
    }

    /**
     * Clean non UTF-8 characters
     *
     * @param string $string
     * @return string
     */
    public function cleanString($string)
    {
        return mb_convert_encoding($string, self::ICONV_CHARSET);
    }
    /**
     * Retrieve string length using default charset
     *
     * @param string $string
     * @return int
     */
    public function strlen($string)
    {
        return mb_strlen($string, self::ICONV_CHARSET);
    }

    /**
     * Filter images with placeholders in the content
     * 
     * @param  string $content
     * @return string
     */
    public function filterCarouselLazyImage($content)
    {
        $matches = $search = $replace = [];
        preg_match_all( '/<img[\s\r\n]+.*?>/is', $content, $matches );
        $placeHolderUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVR4nGNiAAAABgADNjd8qAAAAABJRU5ErkJggg==';
        $lazyClasses    = 'owl-lazy';

        foreach ($matches[0] as $imgHTML) {
            if ( ! preg_match( "/src=['\"]data:image/is", $imgHTML ) && strpos($imgHTML, 'data-src')===false) {

                // replace the src and add the data-src attribute
                $replaceHTML = preg_replace( '/<img(.*?)src=/is', '<img$1src="' . $placeHolderUrl . '" data-src=', $imgHTML );

                // add the lazy class to the img element
                if ( preg_match( '/class=["\']/i', $replaceHTML ) ) {
                    $replaceHTML = preg_replace( '/class=(["\'])(.*?)["\']/is', 'class=$1' . $lazyClasses . ' $2$1', $replaceHTML );
                } else {
                    $replaceHTML = preg_replace( '/<img/is', '<img class="' . $lazyClasses . '"', $replaceHTML );
                }

                $search[]  = $imgHTML;
                $replace[] = $replaceHTML;
            }
        }

        $content = str_replace( $search, $replace, $content );

        return $content;
    }

    /**
     * @param  int $number 
     * @return int         
     */
    public function getResponsiveClass($number)
    {
        if (in_array($number, [1, 2, 3, 4, 6, 12])) {
            return 12 / $number;
        }
        if ($number == 5) {
            return 15;
        }
        return $number;
    }

    /**
     * @param  string $value 
     * @return string        
     */
    public function getStyleColor($value, $isImportant = false)
    {
        if ($value && (!$this->startsWith($value, '#') && !$this->startsWith($value, 'rgb'))) {
            if ($value != 'transparent') {
                $value = '#' . $value;
            }
        }
        if ($value && $isImportant) {
            $value .= ' !important';
        }
        return $value;
    }

    /**
     * @param  string $value
     * @return string       
     */
    public function getStyleProperty($value, $isImportant = false, $unit = '')
    {
        if (is_numeric($value)) {
            if ($unit) {
                $value .= $unit;
            } else {
                $value .= 'px';
            }
        }
        if ($value == '-') $value = '';
        if ($value && $isImportant) {
            $value .= ' !important';
        }
        return $value;
    }

    /**
     * @param  string|array $target 
     * @param  array $styles 
     * @param  string $suffix 
     * @return string         
     */
    public function getStyles($target, $styles, $suffix = '')
    {
        $html = '';
        if (is_array($target)) {
            foreach ($target as $k => $_selector) {
                if (!$_selector) {
                    unset($target[$k]);
                }
            }
            $i = 0;
            $count = count($target);
            foreach ($target as $_selector) {
                $html .= $_selector . $suffix;
                if ($i!=$count-1)  {
                    $html .= ',';
                }
                $i++;
            }
        } else {
            $html = $target . $suffix;
        }
        $stylesHtml = $this->parseStyles($styles);
        if (!$stylesHtml) return;
        if ($styles) {
            $html .= '{';
            $html .= $stylesHtml;
            $html .= '}';
        }
        return $html;
    }

    /**
     * @param  array $styles 
     * @return string       
     */
    public function parseStyles($styles)
    {
        $result = '';
        foreach ($styles as $k => $v) {
            if ($v=='') continue;
            $result .= $k . ':' . $v . ';';
        }
        return $result;
    }

    public function isNull($value) {
        if (is_numeric($value)) return false;
        if ($value === '' || $value === null) {
            return true;
        }
        return false;
    }

    /**
     * @param  string $html 
     * @return string       
     */
    public function cleanStyle($html)
    {
        $regex  = '@(?:<style class="mgz-style">)(.*)</style>@msU';
        preg_match_all($regex, $html, $matches);
        if ($matches[0]) {
            $html = str_replace($matches[0], [], $html);
        }
        return $html;
    }


    public function getLicense($module_name) {
        $ip          = $this->_remoteAddress->getRemoteAddress();
        $file        = $this->_moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, $module_name) . '/license.xml';
        if(file_exists($file)) {
            $xmlObj      = new \Magento\Framework\Simplexml\Config($file);
            $xmlData     = $xmlObj->getNode();
            if ($xmlData) {
                $code = $xmlData->code;
                $license = $this->_license->load($code);
                return $license;
            }
            return false;
        } else {
            return true;
        }
    }
}
