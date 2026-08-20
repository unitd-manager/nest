<?
class CPL_Admin_Modules_Core_Staff_Functions extends CP_Admin_Modules_Core_Staff_Functions
{
    /**
     *
     */
    function setLinksArray($inst) {
        
        $linkObj = $inst->getLinksArrayObj('core_staff', 'project_staffGroupLink');

        $inst->registerLinksArray($linkObj, array(
            'historyTableName' => 'staff_group_history'
           ,'keyField'         => 'staff_group_id'
        ));

        //------------------------------------------------------------------------------//
        if ($_SESSION['userGroupName'] == "Super Administrator") {
            $edit = 1;
        } else {
            $edit = 0;
        }
        
        $linkObj = $inst->getLinksArrayObj('core_staff', 'manPower_staffCommissionLink');
        $inst->registerLinksArray($linkObj, array(
            'historyTableName' => 'staff_commission'
           ,'keyField'         => 'staff_commission_id'
           ,'linkingType'      => 'portal'
           ,'hasPortalEdit'    => $edit
           ,'fieldlabel'       => array('Project Code', 'Date', 'Amount', 'Status')
        ));

        //------------------------------------------------------------------------------//
        $linkObj = $inst->getLinksArrayObj('core_staff', 'tradingsg_productGroupLink', array(
            'historyTableName'       => 'product_group_staff'
           ,'linkingType'            => 'modal'
        ));
        $inst->registerLinksArray($linkObj);
    }
}