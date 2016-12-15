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
namespace Ced\Advancerate\Block\Adminhtml\Carrier\Advancerate;

/**
 * Shipping carrier table rate grid block
 * WARNING: This grid used for export table rates
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_websiteId;

    protected $_tablerate;

    protected $_collectionFactory;
    
    protected $_conditionName;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ced\Advancerate\Model\ResourceModel\Carrier\Advancerate\CollectionFactory $collectionFactory,
        \Ced\Advancerate\Model\Carrier\Advancerate $tablerate,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_tablerate = $tablerate;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Define grid properties
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('shippingTablerateGrid');
        $this->_exportPageSize = 10000;
    }

    /**
     * Set current website
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        $this->_websiteId = $this->_storeManager->getWebsite($websiteId)->getId();
        return $this;
    }

    /**
     * Retrieve current website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        if ($this->_websiteId === null) {
            $this->_websiteId = $this->_storeManager->getWebsite()->getId();
        }
        return $this->_websiteId;
    }

    /**
     * Prepare shipping table rate collection
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Ced\Advancerate\Model\Resource\Carrier\Advancerate\Collection */
        $collection = $this->_collectionFactory->create();
       // $collection->setWebsiteFilter($this->getWebsiteId());
        $collection->setConditionFilter($this->getConditionName())
        ->setWebsiteFilter($this->getWebsiteId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
    
    public function getConditionName()
    {
    	return $this->_conditionName;
    }
    
    public function setConditionName($name)
    {
    	$this->_conditionName = $name;
    	return $this;
    }

    /**
     * Prepare table columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'dest_country',
            ['header' => __('Country'), 'index' => 'dest_country', 'default' => '*']
        );
        $this->addColumn(
            'dest_region',
            ['header' => __('Region/State'), 'index' => 'dest_region', 'default' => '*']
        );
		$this->addColumn(
            'city',
            ['header' => __('City'), 'index' => 'city', 'default' => '*']
        );		
        $this->addColumn(
            'dest_zip',
            ['header' => __('Zip/Postal Code'), 'index' => 'dest_zip', 'default' => '*']
        );		
		$this->addColumn(
            'weight_from',
            ['header' => __('Weight From'), 'index' => 'weight_from', 'default' => '*']
        );		
		$this->addColumn(
            'weight_to',
            ['header' => __('Weight To'), 'index' => 'weight_to', 'default' => '*']
        );		
		$this->addColumn(
            'price_from',
            ['header' => __('Price From'), 'index' => 'price_from', 'default' => '*']
        );		
		$this->addColumn(
            'price_to',
            ['header' => __('Price To'), 'index' => 'price_to', 'default' => '*']
        );		
		$this->addColumn(
            'qty_from',
            ['header' => __('Qty From'), 'index' => 'qty_from', 'default' => '*']
        );		
		$this->addColumn(
            'qty_to',
            ['header' => __('Qty To'), 'index' => 'qty_to', 'default' => '*']
        );
        $this->addColumn(
			'price',
			['header' => __('Shipping Price'), 'index' => 'price']
		);
		$this->addColumn(
			'shipping_label',
			['header' => __('Shipping Method'), 'index' => 'shipping_label']
		);
		
        return parent::_prepareColumns();
    }
}
