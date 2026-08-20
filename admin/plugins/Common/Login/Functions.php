<?
class CPL_Admin_Plugins_Common_Login_Functions
{
    //==================================================================//
    function setPluginArray($plugins){
        $pluginObj = $plugins->getPluginObj('common_login');
    }

    /**
     *
     */
    function setLocalArrayValues(){
        $tv = Zend_Registry::get('tv');

        array_push($tv['protSiteSpActionExceptions'], 'updateTrialToFullVersion');
    }


}