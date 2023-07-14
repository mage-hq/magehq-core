<?php
/**
 * Mageqh
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
 * @category   Mageqh
 * @package    Magehqm2_Core
 * @copyright  Copyright (c) 2022 Magehqm2 (https://magehq.com/)
 * @license    https://magehq.com/license.html
 */

/**
 * Serializer
 */
namespace Magehqm2\Core\Model;

class Serializer
{
	 /**
     * @var null|\Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    private $unserialize;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Unserialize\Unserialize $unserialize
    ) {
        if (interface_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $this->serializer = $objectManager->get(\Magento\Framework\Serialize\SerializerInterface::class);
        }
        $this->unserialize = $unserialize;
    }

    public function serialize($value)
    {
        if ($this->serializer === null) {
            return serialize($value);
        }

        return $this->serializer->serialize($value);
    }

    public function unserialize($value)
    {
        if ($this->serializer === null) {
            return $this->unserialize->unserialize($value);
        }

        try {
            return $this->serializer->unserialize($value);
        } catch (\InvalidArgumentException $exception) {
            return unserialize($value);
        }
    }
}