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

$showProductImage		=	$this->params->get('showProductImage','1');
$showProductTitle		=	$this->params->get('showProductTitle','1');
$showProductDescription	=	$this->params->get('showProductDescription','1');
$showProductDetailsLink	=	$this->params->get('showProductDetailsLink','1');
$showAddToCartButton	=	$this->params->get('showAddToCartButton','1');
$title_color			=	$this->params->get('title_color','#000000');
$desc_color				=	$this->params->get('desc_color','#000000');
$temes_style_size		=	$this->params->get('temes_style_size','300');

if(!isset($temes_style_size) || $temes_style_size < 300 ){
	$temes_style_size=300;
}

?>
<!-- VM Product Import Version 1.0 by www.joomdev.com Start -->
<style>
.vmproductvmimport{
	padding:20px;
	text-align:center;
}
.addtocart-button.btn.btn-default{
	margin:10px;	
}
.vmimportproductLink.btn.btn-info{
	margin:10px;	
}
.vm-img-desc {
    display: none;
}

</style>
<div class="vmproductvmimport" style="border:1px solid #ddd;width:<?php echo $temes_style_size;?>px">
	
	
	<!--If Product Image Enabled-->
	<?php if($showProductImage){ ?>
		<div class="product_image_container">
			<?php echo $list->product_thumb_image; ?>
		</div>
	<?php } ?>
	
	
	<!--If Product Title Enabled-->
	<?php if($showProductTitle){ ?>
		<?php if(! empty($list->product_name)){ ?>
			<div class="product_title_container" style="color:<?php echo $title_color;?>">
				<h3 class="productvmimporttitle"><?php echo $list->product_name; ?></h3>
			</div>
		<?php } ?>
	<?php } ?>
	
	<!--If Product Description Enabled-->	
	<?php if($showProductDescription){?>	
		<div class="product_desc_container" style="color:<?php echo $desc_color;?>">
			<?php echo $list->product_desc; ?>
		</div>
	<?php } ?>
	
	
	<div class="price_container">
		<span class="productvmimportPrice"><?php echo $list->price; ?> </span>
	</div>
	<!--If Product Add to cart Button Enabled-->
	<?php if($showAddToCartButton){?>
		<div class="add_card_btn_container">
			<?php echo $list->addtocart_button; ?>
		</div>
	<?php } ?>
	
	<!--If Product Details Button Enabled-->	
	<?php if($showProductDetailsLink){?>
		<div class="product_details_btn_container">
			<?php echo $list->product_link_button; ?>
		</div>	
	<?php } ?>
	
	
</div>

<!-- VM Product Import Version 1.0 by www.joomdev.com End -->