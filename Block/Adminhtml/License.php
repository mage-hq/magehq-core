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

namespace Magehqm2\Core\Block\Adminhtml;

class License extends \Magento\Framework\View\Element\Template
{
	protected function _toHtml() {
		$extension = $this->getData('extension');
		$module = $this->getData('module');
		if ($extension) {
			$this->_eventManager->dispatch(
				'magehqm2_check_license',
				['obj' => $this,'ex'=>$extension,'module' => $module]
				);
			$extension = str_replace("_", " ", $extension);

			if (!$this->getData('is_valid')) {
				return '<div style="margin-top: 5px;"><div class="messages error"><div class="message message-error" style="margin-bottom: 0;"><div>Module <b>' . $extension . '</b> is not yet registered and the module is locked! Go to <b>Backend > Magehqm2 > Licenses</b> to register the module. Please login to your account in <a target="_blank" href="https://magehq.com">magehq.com</a>, then go to <b>Dashboard > My Downloadable Products</b>, enter your domains to get a new license. Next go to <b>Backend > Magehqm2 > Licenses</b> to save the license.</div></div></div></div>';
	        }
	    }
		return parent::_toHtml();
	}
}