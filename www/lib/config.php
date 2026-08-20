<?
$cpCfg = array();
//$cpCfg['cp.theme'] = 'Default';

$cpCfg['cp.showLoginInfoAtTheTop'] = true;
$cpCfg['cp.showViewCartAtTheTop'] = true;

$modArr = array(
     'webBasic_home'
    ,'webBasic_section'
    ,'webBasic_content'
    ,'webBasic_contactUs'
    ,'ecommerce_product'
    ,'membership_contact'
    ,'ecommerce_basket'
    ,'ecommerce_stockist'
);
$hiddenModules = array();

$cpCfg['cp.availableModules'] = array_merge($modArr, $hiddenModules);

$cpCfg['cp.availableModGroups'] = array(
     'webBasic'
    ,'ecommerce'
    ,'membership'
);

$cpCfg['cp.availableWidgets'] = array(
     'core_mainNav'
    ,'core_subNav'
    ,'core_subCat'
    ,'media_anythingSlider'
    ,'content_record'
    ,'ecommerce_productRecord'
    ,'member_loginForm'
    ,'member_changePassword'
    ,'member_registerForm'
    ,'media_imagesSlider'
    ,'ecommerce_addToCart'
    ,'ecommerce_basket'
    ,'ecommerce_shippingDetails'
    ,'ecommerce_paymentMethods'
    ,'ecommerce_confirmOrder'
);

$cpCfg['cp.availablePlugins'] = array(
     'common_comment'
    ,'common_media'
    ,'common_siteSearch'
    ,'member_login'
    ,'paymentMethods_paypal'
    ,'member_forgotPassword'
);

return $cpCfg;