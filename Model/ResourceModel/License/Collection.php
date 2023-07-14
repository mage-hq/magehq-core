<?php
/**
 * Magehqm2
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the mageqh.com license that is
 * available through the world-wide-web at this URL:
 * https://magehq.com/license.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Magehqm2
 * @package    Magehqm2_Core
 * @copyright  Copyright (c) 2022 Magehqm2 (https://magehq.com/)
 * @license    https://magehq.com/license.html
 */

namespace Magehqm2\Core\Model\ResourceModel\License;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magehqm2\Core\Model\License', 'Magehqm2\Core\Model\ResourceModel\License');
    }
}
