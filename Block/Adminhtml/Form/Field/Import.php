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
namespace Ced\Advancerate\Block\Adminhtml\Form\Field;

/**
 * Custom import CSV file field for shipping table rates
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Import extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';
        $html .= parent::getElementHtml();
        return $html;
    }
}
