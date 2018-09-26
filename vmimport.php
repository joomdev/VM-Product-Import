<?php
// No direct access.
/**
 * @package		VM Product Import
 * @version		1.0
 * @author		JoomDev - www.JoomDev.com
 * @copyright	Copyright (C) 2016  www.JoomDev.com
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die;
class PlgSystemVmimport extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		
		$app 		= JFactory::getApplication();
		if (JFactory::getApplication()->isAdmin())
		{
			return true;
		}
		$this->loadLanguage();		
		return true;
	}
	
	public function onAfterInitialise()
	{
		if(!JFactory::getApplication()->isAdmin()){
			
			jimport( 'joomla.application.component.helper' );
			if ( !JComponentHelper::isEnabled( 'com_virtuemart', true) ) {
				JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_VMIMPORT_SOME_ERROR_OCCURRED'),'error');
			} 
		}
	}
	public function init()
	{
		
		if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/helpers/config.php');
		VmConfig::loadConfig();
		// Load the language file of com_virtuemart.
		JFactory::getLanguage()->load('com_virtuemart');
		if (!class_exists( 'calculationHelper' )) require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/helpers/calculationh.php');
		if (!class_exists( 'CurrencyDisplay' )) require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/helpers/currencydisplay.php');
		if (!class_exists( 'VirtueMartModelVendor' )) require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/models/vendor.php');
		if (!class_exists( 'VmImage' )) require(JPATH_ADMINISTRATOR .'/components/com_virtuemart/helpers/image.php');
		if (!class_exists( 'shopFunctionsF' )) require(JPATH_SITE.'/components/com_virtuemart/helpers/shopfunctionsf.php');
		if (!class_exists( 'calculationHelper' )) require(JPATH_COMPONENT_SITE.'/helpers/cart.php');
		if (!class_exists( 'VirtueMartModelProduct' )){
		   JLoader::import( 'product', JPATH_ADMINISTRATOR .'/components/com_virtuemart/models' );
		}
	}
	public function onAfterRender()
	{
		$app 		= JFactory::getApplication();
		if (JFactory::getApplication()->isAdmin())
		{
			return true;
		}
		// only in html
		if (JFactory::getDocument()->getType() !== 'html')
		{
			return;
		}
		$html = JFactory::getApplication()->getBody();
		if ($html == '')
		{
			return;
		}
		$regex1	= '/{\s?vmimport\s+(.*?)}/i';			
		if(preg_match_all($regex1,$html,$matches, PREG_SET_ORDER))
		{	
			if((isset($matches[0][0]) && !empty($matches[0][0])) && (isset($matches[0][1]) && !empty($matches[0][1]))){
				$findtext = $matches[0][0];
				$pluginParams = $this->params;
				$params_init = array('id' => $matches[0][1],
								 'sku' => '',							 
								 'showProductImage' =>$pluginParams->get('showProductImage'), 
								 'showProductTitle' =>$pluginParams->get('showProductTitle'),							 
								 'showProductDescription' => $pluginParams->get('showProductDescription'), 
								 'showAddToCartButton' =>$pluginParams->get('showAddToCartButton'),
								 'showProductDetailsLink' => $pluginParams->get('showProductDetailsLink'),
								);	
								
				$VMProduct = $this->getVMProduct($pluginParams, $params_init);
				$output = $this->getOutput($VMProduct, $pluginParams, $user_params, 'default.php');
				$html = JString::str_ireplace($findtext,$output,$html);
				JFactory::getApplication()->setBody($html);	
				$renderPlugin = true;
			}
		}
	}
	public function onContentPrepare($context, $article, $params, $page = 0)
	{
		$app 		= JFactory::getApplication();
		if (JFactory::getApplication()->isAdmin())
		{
			return true;
		}
		$renderPlugin = false;
		$pluginParams = $this->params;
	 	$params_init = array('id' => '',
							 'sku' => '',							 
		                     'showProductImage' =>1, 
							 'showProductTitle' => 1,							 
							 'showProductDescription' => 1, 
							 'showAddToCartButton' => 1,														 
							 'showProductDetailsLink' => 1,							 
							 );		
		
		$permittedKeys = array_keys($params_init);
		$param_defaults = array();
	    foreach( $params_init as $key => $value ) {
		   $param_defaults[$key] = $pluginParams->get( $key, $value ) ;
	    }
		
		$user_params = array();
		$regex	= '/{\s?vmimport\s+(.*?)}/i';	
		if(preg_match_all($regex, $article->text,$matches, PREG_SET_ORDER))
		{	
		
			$this->init();
			$i=0;
			foreach ($matches as $match) {
				
				if(isset($match[1])){
					$user_params[$i] = $this->get_params($match[1], $param_defaults, $permittedKeys );
					
				}
				else
				{
					$user_params[$i] = $param_defaults;
				}
				
				if(! empty($user_params[$i]['id']) || ! empty($user_params[$i]['sku']))
				{
					
					$VMProduct = $this->getVMProduct($pluginParams, $user_params[$i]);
					$output = $this->getOutput($VMProduct, $pluginParams, $user_params, 'default.php');
					
					//echo  $article->text;
				    $findtext = $match[0];
					$article->text = JString::str_ireplace($findtext, $output, $article->text);
					$renderPlugin = true;
				}
				$i++;
			}
		}
		$loadStylesheet = (bool)$pluginParams->get('loadStylesheet',1);
		$baseurl  = '';
		if($renderPlugin && $loadStylesheet)
		{
		  $doc = JFactory::getDocument();
		//only load stylesheet if plugin is being rendered
		  if(file_exists(JPATH_SITE.'/plugins/system/vmimport/vmimport/css/vmimport.css'))
		  {
			 JHTML::_('stylesheet','plugins/system/vmimport/vmimport/css/vmimport.css');
		  }
		}
		if($renderPlugin)
		{
		 JHTML::_('behavior.modal', 'a.vm_modal');
		}
		return true;
	}
	
	 
	function get_params( &$match, $param_defaults, $permittedKeys ) {
		$match = str_ireplace($permittedKeys,$permittedKeys, $match); //make case insensitive
	    $params_init = $param_defaults;
		$params = explode( ";", $match ) ;
		foreach( $params as $param ) {
		$param = explode( " ", $param ) ;
		if( (isset($param[0]) && !empty($param[0])) ) {
				$params_init['id'] = str_replace('&','',$param[0]) ;
			}
		}
		return $params_init ;
	}
	
	
	function getVMProduct(&$params, $userparams)
	{
				
		// load the admin language file
		JFactory::getLanguage()->load('plg_' . $this->_type . '_' . $this->_name, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name);
		
		$product_id = (int)$userparams['id'];
		if($product_id == 0){ 
		   $sku = $userparams['sku'];
		   if(empty($sku)){return null;}
		   $db = JFactory::getDBO();
		   $query = $db->getQuery(true);
		   $query->select('virtuemart_product_id');
		   $query->from('#__virtuemart_products');
		   $sku = $db->quote($sku);
		   $query->where('product_sku='.$sku.' AND published=\'1\'');		   
			$db->setQuery($query);
			if(!$product_id = $db->loadResult())
			{
			  return null;  
			}
		}
		
	    $mime_array = array('image/jpeg','image/png','image/gif');
		
        $showProductImage 		= (bool)$userparams['showProductImage']; // Display the Product Price?
		$showProductTitle 		= (bool)$userparams['showProductTitle'];
		$showProductDescription = (bool)$userparams['showProductDescription'];
		$showAddToCartButton 	= (bool)$userparams['showAddToCartButton'];
		$showProductDetailsLink	= (bool)$userparams['showProductDetailsLink'];		
			
		$showPrice				=	1;
		$productModel = VmModel::getModel('Product');
		$product = $productModel->getProduct($product_id);
		
		if(!(bool)$product->published)
		{
			return null;
		}
		
		if($showProductImage)
		{
		   $productModel->addImages($product);
		}
        $list = new stdClass();
		$list->product_id = $product_id;
		$list->category_id = $product->virtuemart_category_id;
		if($showProductTitle)
		{
		  $list->product_name = $product->product_name;
		}
		else
		{
		  $list->product_name = '';	
		}
		
		$list->product_link = $product->link;
		$list->product_link_button = '';
 
		$linkcolor=$this->params->get('hshc_link_details_color','#000000');
		if($showProductDetailsLink)
		{
			$list->product_link_button = '<a class="vmimportproductLink btn btn-info" style="color:'.$linkcolor.';" href="'.$list->product_link.'">'.$this->params->get('hshc_link_details','Product Details').'</a>';
		}
		
		$list->addtocart = JText::_('PLG_SYSTEM_VMIMPORT_ADD_TO_CART');
		
		
		$list->product_thumb_image = '';
		
		if($showProductImage && (count($product->images) > 0))
		{
	 		$list->product_thumb_image = $product->images[0]->displayMediaFull('class="medium-image" title="'.$product->product_name.'" ',true,'class="vm_modal"');
		}
		
		$list->product_desc = '';
		if($showProductDescription)
		{
			$list->product_desc = $product->product_desc;
		}
		
				
		$list->price = '';
		$list->addtocart_link = '';
		$list->addtocart_button = '';
		$stockhandle = VmConfig::get ('stockhandle', 'none');
		$instock = true;
		if (($stockhandle == 'disableit' or $stockhandle == 'disableadd') and ($product->product_in_stock - $product->product_ordered) < 1) {
			$instock = false;
		}
		if (!VmConfig::get('use_as_catalog',0) && $showPrice && $showAddToCartButton) {
	        $currency = CurrencyDisplay::getInstance( );
           if (!empty($product->prices['salesPriceWithDiscount']) ) $list->price .= $currency->createPriceDiv('salesPriceWithDiscount','',$product->prices,true);			
           else if (!empty($product->prices['salesPrice'] ) ) {$list->price .= $currency->createPriceDiv('salesPrice','',$product->prices,true);}
		}
		if (!VmConfig::get('use_as_catalog',0) && $showAddToCartButton && $instock 
				&& !empty( $product->prices['salesPrice']) // Product must have a price to add it to cart
				) 
		{
			$url = "?option=com_virtuemart&view=cart&task=add&quantity[]=1&virtuemart_product_id[]=" .  $product_id.'&virtuemart_category_id[]='.$product->virtuemart_category_id;
			$addtocart_link = JRoute::_("index.php" . $url);
			$list->addtocart_link = $addtocart_link;
			$list->addtocart_button = '<div class="addtocart-button btn btn-default"><a class="addtocart-button" href="'.$list->addtocart_link.'">'.$list->addtocart.'</a></div>';
		}	
	
		return $list;
		
	}
	
	function getOutput( &$list, &$params, &$user_params, $layout='default.php'){
			if(!isset($list)){return '';}						
			ob_start();
			$tmplPath = $this->getTemplatePath('vmimport',$layout);
			$tmplPath = $tmplPath->file;
			include($tmplPath);
			$output = ob_get_contents();
			ob_end_clean();
			
			return $output;
		
	}
	
	function getTemplatePath($pluginName,$file){
		$mainframe		= JFactory::getApplication();
		$p = new JObject;
		if(file_exists(JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/'.$pluginName.'/'.$file)){
			$p->file = JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/'.$pluginName.'/'.$file;
			$p->http = JURI::base()."templates/".$mainframe->getTemplate()."/html/{$pluginName}/{$file}";
		} else if(file_exists(JPATH_SITE.'/plugins/system/'.$pluginName.'/tmpl/'.$file)){
			$p->file = JPATH_SITE.'/plugins/system/'.$pluginName.'/tmpl/'.$file;
			$p->http = JURI::base()."plugins/system/{$pluginName}/tmpl/{$file}";
		}
		else
		{
			$p->file = JPATH_SITE.'/plugins/system/'.$pluginName.'/'.$pluginName.'/tmpl/'.$file;
			$p->http = JURI::base()."plugins/system/{$pluginName}/{$pluginName}/tmpl/{$file}";
		}
		return $p;
	}
	
}