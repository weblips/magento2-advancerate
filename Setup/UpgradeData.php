<?php
namespace Ced\Advancerate\Setup;

use Magento\Directory\Helper\Data;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
	/**
* Directory data
*
* @var Data
*/
private $directoryData;

/**
* Init
*
* @param Data $directoryData
*/
public function __construct(Data $directoryData)
{
	$this->directoryData = $directoryData;
}

 public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
 {

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
			$connection = $setup->getConnection();
			$tableName = $setup->getTable('advance_rate');
			$sql = "ALTER TABLE " . $tableName . " MODIFY city VARCHAR(255)";

			$connection->query($sql);
        }
        
   	}
} 
?>
