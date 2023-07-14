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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class AbstractData extends AbstractHelper
{
	protected $storeManager;
	protected $objectManager;

	public function __construct(
		Context $context,
		ObjectManagerInterface $objectManager,
		StoreManagerInterface $storeManager
	)
	{
		$this->objectManager   = $objectManager;
		$this->storeManager    = $storeManager;
		parent::__construct($context);
	}

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field,
			ScopeInterface::SCOPE_STORE,
			$storeId
		);
	}

	public function getCurrentUrl(){
		$model=$this->objectManager->get('Magento\Framework\UrlInterface');
		return $model->getCurrentUrl();
	}
}