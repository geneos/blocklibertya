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

if (!defined('_PS_VERSION_'))
	exit;

if (!defined('_CAN_LOAD_FILES_'))
	exit;

include_once(dirname(__FILE__) . '/BlockLibertyaModel.php');

class BlockLibertya extends Module {

	private $_html;
	
    function __construct() {
        $this->name = 'blocklibertya';
        $this->tab = 'front_office_features';
        $this->version = 1.0;
        $this->author = 'Cooperativa GENEOS';
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Integración con Libertya');
        $this->description = $this->l('Integración con Libertya');
    }
    
    protected function _postValidation()
    {
    	
    	return true;
    }
    
    protected function _postProcess()
    {
    	
    	if ($this->_postValidation() == false)
    		return false;
    	
    	$this->_errors = array();
    	
    	if (Tools::isSubmit('submitLibertyaConfig'))
    	{   		
    		Configuration::updateValue('WS_URL', Tools::getValue('WS_URL_field'));
    		Configuration::updateValue('ADMIN_USER', Tools::getValue('ADMIN_USER_field'));
    		Configuration::updateValue('ADMIN_PASSWORD', Tools::getValue('ADMIN_PASSWORD_field'));
    		Configuration::updateValue('ADMIN_ORG_ID', Tools::getValue('ADMIN_ORG_ID_field'));
    		Configuration::updateValue('ADMIN_CLIENT_ID', Tools::getValue('ADMIN_CLIENT_ID_field'));

    		$this->_html .= $this->displayConfirmation($this->l('WebService configuration updated.'));
    	}
    	elseif (Tools::isSubmit('submitCreateOrderConfig'))
    	{
    		Configuration::updateValue('ORDER_STATUS_ID', Tools::getValue('ORDER_STATUS_ID_field'));
    		Configuration::updateValue('ORDER_WAREHOUSE', Tools::getValue('ORDER_WAREHOUSE_field'));
    		Configuration::updateValue('ORDER_CURRENCY', Tools::getValue('ORDER_CURRENCY_field'));
    		Configuration::updateValue('ORDER_DOCTYPE_TARGET', Tools::getValue('ORDER_DOCTYPE_TARGET_field'));
    		Configuration::updateValue('ORDER_PRICELIST', Tools::getValue('ORDER_PRICELIST_field'));
    		Configuration::updateValue('ORDER_DESCRIPTION', Tools::getValue('ORDER_DESCRIPTION_field'));
    		
    		$this->_html .= $this->displayConfirmation($this->l('Order Create configuration updated'));
    		
    	}
    	
    	if (count($this->_errors))
    	{
    		foreach ($this->_errors as $err)
    			$this->_html .= '<div class="alert error">'.$err.'</div>';
    	}
    }

   function install() {
       if (!parent::install()
	  || !$this->registerHook('actionOrderStatusUpdate')
       	|| !Configuration::updateValue('WS_URL', 'http://localhost:8080')
		|| !Configuration::updateValue('ADMIN_USER', 'AdminLibertya')
		|| !Configuration::updateValue('ADMIN_PASSWORD', 'AdminLibertya')
		|| !Configuration::updateValue('ADMIN_ORG_ID', '1010053')
		|| !Configuration::updateValue('ADMIN_CLIENT_ID', '1010016')
       	|| !Configuration::updateValue('ORDER_WAREHOUSE', '1010048')
       	|| !Configuration::updateValue('ORDER_CURRENCY', '118')
       	|| !Configuration::updateValue('ORDER_DOCTYPE_TARGET', '1010507')
       	|| !Configuration::updateValue('ORDER_PRICELIST', '1010595')
       	|| !Configuration::updateValue('ORDER_DESCRIPTION', 'Orden creada desde Prestashop')
       	|| !Configuration::updateValue('ORDER_STATUS_ID', '3')
	  )
	  	return false;
      return true;
   }
	
   private function existsOrder($params) {
   	$userName = (string) Configuration::get('ADMIN_USER');
   	$password = (string) Configuration::get('ADMIN_PASSWORD');
   	$clientID = (string) Configuration::get('ADMIN_CLIENT_ID');
   	$orgID = (string) Configuration::get('ADMIN_ORG_ID');
   	
   	$libertyaUrl= (string) Configuration::get('WS_URL');
	$wsUrl=$libertyaUrl."/axis/services/LibertyaWS?wsdl";
   	
   	$orderNro = $params["id_order"];
	
	$client = new SoapClient($wsUrl);

	$ParameterBean = array(
		'userName' => $userName,
		'password' => $password,
		'clientID' => $clientID,
		'orgID' => $orgID,
		'mainTable' => null
	);

	$in1 = 'DocumentNo';//Nombre de la columna
	$in2 = (string)$orderNro; //C_BPartner_Value

	$result = $client->documentRetrieveOrderByColumn( $ParameterBean, $in1, $in2);
	
	return !$result->error; //Da error si no la encuentra
   }

   private function createLibertyaOrder($params) {
	$cart = $params["cart"];
	$orderNro = $params["id_order"]; //id_order (Presta) <=> DocumentNo (Libertya)

	$productsCart = $cart->getProducts();

	

	$userName = (string) Configuration::get('ADMIN_USER');
	$password = (string) Configuration::get('ADMIN_PASSWORD');
	$clientID = (string) Configuration::get('ADMIN_CLIENT_ID');
	$orgID = (string) Configuration::get('ADMIN_ORG_ID');
	
	$libertyaUrl= (string) Configuration::get('WS_URL');
	$wsUrl=$libertyaUrl."/axis/services/LibertyaWS?wsdl";

	$client = new SoapClient($wsUrl);
	
	//Obtener productos segun value desde libertya	
	$ParameterBean = array(
		'userName' => $userName,
		'password' => $password,
		'clientID' => $clientID,
		'orgID' => $orgID,
		'mainTable' => null
	);
	
	$i = 1;
	foreach ($productsCart as $productCart){
		$value = $productCart["reference"]; //Reference (Presta) <=> Value (Libertya)
		//Llamo a WS para obtener producto
		$result = $client->productRetrieveByValue( $ParameterBean, $value );
		if (!$result->error) {
			$documentLines[] = array (
				'Line' => (string)$i,
				'M_Product_ID' => (string)$result->mainResult["M_Product_ID"],
				'QtyEntered' => (string)$productCart["cart_quantity"],		
				);
			$i++;
		}
		else {
			Logger::addLog('Error al obtener producto '.$value.' desde libertya: '.$result->errorMsg, 5);
			return false;
		}
	}
	

	
	
	//Armo el Pedido

	$warehouse = (string) Configuration::get('ORDER_WAREHOUSE');
	$currency = (string) Configuration::get('ORDER_CURRENCY');
	$docTarget = (string) Configuration::get('ORDER_DOCTYPE_TARGET');
	$priceList = (string) Configuration::get('ORDER_PRICELIST');
	$description = (string) Configuration::get('ORDER_DESCRIPTION');
	
	$OrderParameterBean = array(
		// Login de libertya:
		'userName' => $userName,
		'password' => $password,
		'clientID' => $clientID,
		'orgID' => $orgID,
		// Cabecera Orden:
		'mainTable' => array(
			'C_DocTypeTarget_ID' => $docTarget, 
			'M_PriceList_ID' => $priceList,
			'C_Currency_ID' => $currency,
			'PaymentRule' => 'Tr',
			'CreateCashLine' => 'N',
			'DocumentNo' => (string)$orderNro,
			'ManualGeneralDiscount' => '0.00',
			'M_Warehouse_ID' => $warehouse, 
			'Description' => $description
			),
		'documentLines' => $documentLines,
		'invoiceDocTypeTargetID' => null,
		'invoicePuntoDeVenta' => null,
		'invoiceTipoComprobante' => null
	);
	

	$in1 = 0;//Dejo en 0 para que me busque socio de negocio por Value.
	$in2 = 'CF'; //C_BPartner_Value
	$in3 = null; //TaxID
	$in4 = false; //Complete Order?
	$in5 = false; //Create invoice?
	$in6 = false; //Complete invoice?

	$result = $client->orderCreateCustomer( $OrderParameterBean, $in1, $in2, $in3, $in4, $in5, $in6 );

	//Chequeo si el WS me dio error
	if ($result->error){
		Logger::addLog('Error al crear orden '.$orderNro.' desde prestashop a libertya: '.$result->errorMsg, 5);
		return false;
	}

	return true;
   }

   public function hookActionOrderStatusUpdate($params){
	$error = false;
	
	//Al pasar a que estado deberia crear la orden?
	$idEstadoCrearOrden = (int)Configuration::get('ORDER_STATUS_ID');
	
	$idEstadoOrdenErronea = 14;
	$newOrderStatus = $params["newOrderStatus"];

	$success = true;
	


	if ($newOrderStatus->id === $idEstadoCrearOrden  && !$this->existsOrder($params,$wsUrl,$userName,$password,$clientID,$orgID))
		$success = $this->createLibertyaOrder($params,$wsUrl,$userName,$password,$clientID,$orgID);

	if ($success)
		Logger::addLog('Pedido '.$params["id_order"].' creado con exito en libertya', 1);
	else {
		$history = new OrderHistory();
		$history->id_order = (int)$params["id_order"];
		var_dump($history->changeIdOrderState($idEstadoOrdenErronea, (int)$params["id_order"]));
		print_r($history->changeIdOrderState($idEstadoOrdenErronea, (int)$params["id_order"]));
		Logger::addLog('Resincronizar '.$orderNro.' desde prestashop a libertya', 5);
	}


   } 


public function getContent () {

	$this->_html = '';
	$this->_postProcess();


	$this->displayForm();

	return $this->_html;
	
	}

private function displayForm () {

	

	$this->context->controller->addJqueryPlugin('tablednd');
	$this->context->controller->addJS(_PS_JS_DIR_.'admin-dnd.js');
	
	$current_index = AdminController::$currentIndex;
	$token = Tools::getAdminTokenLite('AdminModules');

	$this->_display = 'index';
	
	$this->fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Webservice Configuration'),
				'icon' => 'icon-list-alt'
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Libertya Host URL'),
					'name' => 'WS_URL_field',
					//'desc' => $this->l('Who user manage the WS workflow?')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Client ID'),
					'name' => 'ADMIN_CLIENT_ID_field',
					//'desc' => $this->l('Who user manage the WS workflow?')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Org ID'),
					'name' => 'ADMIN_ORG_ID_field',
					//'desc' => $this->l('Who user manage the WS workflow?')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Admin User'),
					'name' => 'ADMIN_USER_field',
					//'desc' => $this->l('Who user manage the WS workflow?')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Admin Password'),
					'name' => 'ADMIN_PASSWORD_field',
					//'desc' => $this->l('Who user manage the WS workflow?')
				),				
				
			),
			'submit' => array(
				'name' => 'submitLibertyaConfig',
				'title' => $this->l('Save'),
			)
		);	
	
	$this->fields_form[1]['form'] = array(
			'legend' => array(
					'title' => $this->l('Order Create Configuration'),
					'icon' => 'icon-list-alt'
			),
			'input' => array(
					array(
							'type' => 'text',
							'label' => $this->l('Prestashop Status ID for Hook'),
							'name' => 'ORDER_STATUS_ID_field',
							//'desc' => $this->l('Who user manage the WS workflow?')
					),
					array(
							'type' => 'text',
							'label' => $this->l('Warehouse ID'),
							'name' => 'ORDER_WAREHOUSE_field',
							//'desc' => $this->l('Who user manage the WS workflow?')
					),
					array(
							'type' => 'text',
							'label' => $this->l('Currency ID'),
							'name' => 'ORDER_CURRENCY_field',
							//'desc' => $this->l('Who user manage the WS workflow?')
					),
					array(
							'type' => 'text',
							'label' => $this->l('DocType Target ID'),
							'name' => 'ORDER_DOCTYPE_TARGET_field',
							//'desc' => $this->l('Who user manage the WS workflow?')
					),
					array(
							'type' => 'text',
							'label' => $this->l('Price List ID'),
							'name' => 'ORDER_PRICELIST_field',
							//'desc' => $this->l('Who user manage the WS workflow?')
					),
					array(
							'type' => 'textarea',
							'rows' => 5,
							'cols' => 60,
							'label' => $this->l('Order Description'),
							'name' => 'ORDER_DESCRIPTION_field',
							//'desc' => $this->l('Who user manage the WS workflow?')
					),
			),
			'submit' => array(
					'name' => 'submitCreateOrderConfig',
					'title' => $this->l('Save'),
			)
	);
	
	$this->fields_value['WS_URL_field'] = Configuration::get('WS_URL');
	$this->fields_value['ADMIN_USER_field'] = Configuration::get('ADMIN_USER');
	$this->fields_value['ADMIN_PASSWORD_field'] = Configuration::get('ADMIN_PASSWORD');
	$this->fields_value['ADMIN_ORG_ID_field'] = Configuration::get('ADMIN_ORG_ID');
	$this->fields_value['ADMIN_CLIENT_ID_field'] = Configuration::get('ADMIN_CLIENT_ID');
	
	$this->fields_value['ORDER_STATUS_ID_field'] = Configuration::get('ORDER_STATUS_ID');
	$this->fields_value['ORDER_WAREHOUSE_field'] = Configuration::get('ORDER_WAREHOUSE');
	$this->fields_value['ORDER_CURRENCY_field'] = Configuration::get('ORDER_CURRENCY');
	$this->fields_value['ORDER_DOCTYPE_TARGET_field'] = Configuration::get('ORDER_DOCTYPE_TARGET');
	$this->fields_value['ORDER_PRICELIST_field'] = Configuration::get('ORDER_PRICELIST');
	$this->fields_value['ORDER_DESCRIPTION_field'] = Configuration::get('ORDER_DESCRIPTION');
	
	$helper = $this->initForm();
	$helper->submit_action = '';
	$helper->title = $this->l('Configuración de Modulo Libertya');

	$helper->fields_value = $this->fields_value;
	$this->_html .= $helper->generateForm($this->fields_form);
	}

	public function initForm()
	{ 
		$helper = new HelperForm();

		$helper->module = $this;
		$helper->name_controller = 'blocklibertya';
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->languages = $this->context->controller->_languages;
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->default_form_language = $this->context->controller->default_form_language;
		$helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;

		return $helper;

	}
	

}
?>
