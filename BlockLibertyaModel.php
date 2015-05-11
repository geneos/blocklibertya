<?php
/*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Cooperativa GENEOS <info@geneos.com.ar>
*  @copyright  2013-2015 GENEOS SRL
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class BlockLibertyaModel extends ObjectModel
{
	public $id_libertya_block;


	/**
	 * @see ObjectModel::$definition
	 */

	
	public static $definition = array(
		'table' => 'libertya_block',
		'primary' => 'id_libertya_block',
		'fields' => array(
			'id_libertya_block' =>       array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_ps_order' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_ly_order' =>           array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
		),
	);

	public static function createTables()
	{
		return (
			BlockLibertyaModel::createLibertyaOrderTable()
		);
	}

	public static function dropTables()
	{
		$sql = 'DROP TABLE
			`'._DB_PREFIX_.'libertya_block`';

		return Db::getInstance()->execute($sql);
	}

	public static function createLibertyaOrderTable()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'libertya_block`(
			`id_libertya_block` int(10) unsigned NOT NULL auto_increment,
			`id_ps_order` int(10) unsigned NOT NULL,
			`id_ly_order` int(10) unsigned NOT NULL,
			PRIMARY KEY (`id_libertya_block`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

		return Db::getInstance()->execute($sql);
	}
	

	public static function insertLibertyaOrder($id_ps_order, $id_ly_order)
	{
		$sql = 'INSERT INTO `'._DB_PREFIX_.'libertya_block` (`id_ps_order`, `id_ly_order`)
			VALUES('.(int)$id_ps_order.', '.(int)$id_ly_order.')';

		if (Db::getInstance()->execute($sql))
            return Db::getInstance()->Insert_ID();

        return false;
	}

	

	

	public static function deleteLibertyaOrder($id_libertya_block)
	{
		$sql = 'DELETE FROM `'._DB_PREFIX_.'cms_block`
				WHERE `id_cms_block` = '.(int)$id_cms_block;

		Db::getInstance()->execute($sql);
	}

	

}
