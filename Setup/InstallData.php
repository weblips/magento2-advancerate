<?php
namespace Ced\Advancerate\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
	}
	
	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'dimension_package_height',
			[
				'type' => 'varchar',
                'label' => 'Dimension Package Height',
                'input' => 'text',
                'required' => false,
                'sort_order' => 200,
                'group' => 'Product Details',
                'used_in_product_listing' => true,
                'visible_on_front' => false
			]
		);

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'dimension_package_length',
			[
				'type' => 'varchar',
                'label' => 'Dimension Package Length',
                'input' => 'text',
                'required' => false,
                'sort_order' => 201,
                'group' => 'Product Details',
                'used_in_product_listing' => true,
                'visible_on_front' => false
			]
		);

		$eavSetup->addAttribute(
			\Magento\Catalog\Model\Product::ENTITY,
			'dimension_package_width',
			[
				'type' => 'varchar',
                'label' => 'Dimension Package Height',
                'input' => 'text',
                'required' => false,
                'sort_order' => 202,
                'group' => 'Product Details',
                'used_in_product_listing' => true,
                'visible_on_front' => false
			]
		);





	}
}