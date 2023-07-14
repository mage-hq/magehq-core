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

namespace Magehqm2\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

class AdminSystemConfigSave implements ObserverInterface
{
	protected $configWriter;

    protected $cacheTypeList;

    protected $cacheFrontendPool;

	public function __construct(
		\Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
         TypeListInterface $cacheTypeList, 
        Pool $cacheFrontendPool
		) {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
	}

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$configData        = $observer->getConfigData();
        $request = $observer->getRequest();
        if(!$configData || ($configData && isset($configData['groups']) && !$configData['groups'])){
            $groups = $request->getParam('groups');
            if($groups && isset($groups['general']) && $groups['general']){
                $modules = $groups['general']['fields'];
                if($modules){
                    foreach($modules as $key=>$item){
                        $module_license_key = isset($item['value'])?$item['value']:'';
                            $this->configWriter->save('magehqm2license/general/'.$key,  $module_license_key, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                            $this->flushCache();
                        
                    }
                }
            }
        }
		
	}

    public function flushCache()
    {
      $_types = [
                'config',
                'layout',
                'block_html',
                'collections',
                'reflection',
                'db_ddl',
                'eav',
                'config_integration',
                'config_integration_api',
                'full_page',
                'translate',
                'config_webservice'
                ];
     
        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}