<?php
/**
 * CedCommerce
  *
  * NOTICE OF LICENSE
  *
  * This source file is subject to the End User License Agreement (EULA)
  * that is bundled with this package in the file LICENSE.txt.
  * It is also available through the world-wide-web at this URL:
  * http://cedcommerce.com/license-agreement.txt
  *
  * @category    Ced
  * @package     Ced_Advancerate
  * @author       CedCommerce Core Team <connect@cedcommerce.com >
  * @copyright   Copyright CEDCOMMERCE (http://cedcommerce.com/)
  * @license      http://cedcommerce.com/license-agreement.txt
  */
namespace Ced\Advancerate\Model\Option;

class Condition
{
	/**
     * Options getter
     *
     * @return array
     */
public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('Weight And Destination')], ['value' => 1, 'label' => __('Order Subtotal And Destination')],['value' => 2, 'label' => __('Quantity And Destination')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [2 => __('Quantity And Destination'), 1 => __('Order Subtotal And Destination'),0 => __('Weight And Destination')];
    }
}
