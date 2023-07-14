<?php
/**
 * Magehq
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
 * @category   magehq
 * @package    Magehq_Core
 * @copyright  Copyright (c) 2022 magehq (https://magehq.com/)
 * @license    https://magehq.com/license.html
 */

namespace Magehq\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Dir;

class CheckLicense implements ObserverInterface
{
	/**
     * @var \Magehq\Core\Model\License
     */
	protected $_license;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

	/**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
	protected $_remoteAddress;

	/**
	 * @param \Magehq\Core\Model\License                               $license        
	 * @param \Magento\Framework\Module\Dir\Reader                 $moduleReader   
	 * @param \Magento\Store\Model\StoreManagerInterface           $storeManager   
	 * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress  
	 * @param \Magento\Framework\Message\ManagerInterface          $messageManager 
	 */
	public function __construct(
		\Magehq\Core\Model\License $license,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magehq\Core\Helper\Data $licenseHelper
		) {
		$this->_license       = $license;
		$this->messageManager = $messageManager;
		$this->_storeManager  = $storeManager;
		$this->_remoteAddress = $remoteAddress;
		$this->licenseHelper  = $licenseHelper;
	}

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$ip         = $this->_remoteAddress->getRemoteAddress();
		$obj        = $observer->getObj();
		$moduleName = $observer->getEx();
		$license    = $this->licenseHelper->getLicense($moduleName);
		
		if (($license && is_bool($license)) || ($license && $license->getStatus())) {
			$obj->setData('is_valid', 1);
		} else {
			$obj->setData('is_valid',0);
			if ($ip == '127.0.0.1' || $ip == '172.17.0.1' || $ip == '172.19.0.1') {
				$obj->setData('is_valid', 1);
			} else {
				$obj->setData('is_valid', 0);
			}
		}
		//$obj->setData('is_valid', 0);
	}
}