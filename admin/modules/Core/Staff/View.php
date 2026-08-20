<?
class CPL_Admin_Modules_Core_Staff_View extends CP_Admin_Modules_Core_Staff_View
{

    /**
     *
     */
    function getEdit($row) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $formObj = Zend_Registry::get('formObj');
        $ln = Zend_Registry::get('ln');
        $am = Zend_Registry::get('am');

        $formObj->mode = $tv['action'];

        $staffTeam        = "";
        $staffType        = "";
        $sectionName      = "";
        $staffRate        = "";
        $sensDetails      = "";
        $description      = "";
        $userGroup        = "";
        $shortCode        = "";

        $expVl = array('sqlType' => 'OneField');

        $expNoedit = array('isEditable' => 0);
        if($_SESSION['isDeveloper'] == 1){
            $expNoedit = "";
        }

        if ($cpCfg['cp.hasProjectMg'] == 1) {
            $sqlTeam = $fn->getValueListSQL('staffTeam');
            $sqlType = $fn->getValueListSQL('staffType');

            $staffType   = $formObj->getDDRowBySQL('Staff Type', 'staff_type', $sqlType, $row['staff_type'], $expVl);
            $staffTeam   = $formObj->getDDRowBySQL('Staff Team', 'team', $sqlTeam, $row['team'], $expVl);
            if ($cpCfg['m.core.staff.showFldSensitiveDetails'] == 1){
                $sensDetails = $formObj->getYesNoRRow('Show Sensitive Details', 'show_sensitive_details', $row['show_sensitive_details']);
            }

            $exp = array('isEditable' => 0);
            if ($_SESSION['userGroupName'] == 'Super Administrator'){
                $staffRate   = $formObj->getTBRow('Staff Rate', 'staff_rate', $row['staff_rate']);
            } else {
                $staffRate   = $formObj->getTBRow('Staff Rate', 'staff_rate', $row['staff_rate'], $exp);
            }
        }

        if ($cpCfg['cp.hasFirstRoomValueInStaff'] == 1) {
            $sectionName = $formObj->getDDRowByArr("Login Section Default", "section_name", $am->getSectionNameArray(), $row['section_name']);
        }

        if ($cpCfg['m.core.staff.showStaffDescription'] == 1) {
            $description = $formObj->getHTMLEditor('Description', 'description', $ln->gfv($row, 'description', '0'));
        }

        $fnMod = includeCPClass('ModuleFns', 'core_staff');

        $userGrp = '';

        if ($cpCfg['m.core.staff.showUserGroup'] == 1 || $cpCfg['cp.hasAccessModule']){
            
            $exp = array('isEditable' => 0, 'hideFirstOption' => 1, 'detailValue' => $row['user_group_title']);
            if($_SESSION['isDeveloper'] == 1){
                $exp = array('hideFirstOption' => 1, 'detailValue' => $row['user_group_title']);
            }

            $sqlUG = "
            SELECT user_group_id
                  ,title
            FROM {$cpCfg['cp.modAccessUserGroupTable']}
            ";

            $sqlUG = $fn->getSQL($sqlUG);
            $userGrp = $formObj->getDDRowBySQL($ln->gd('m.core.staff.lbl.userGroup', 'User Group'), 'user_group_id', $sqlUG, $row['user_group_id'], $exp);
        }

        if ($cpCfg['m.core.staff.showShortCode'] == 1){
            $shortCode = $formObj->getTBRow('Short Code', 'short_code', $row['short_code']);
        }

        $fnModCountry = includeCPClass('ModuleFns', 'common_country');

        $zipCodeText = '';
        if ($cpCfg['m.core.staff.hasZipCode']) {
            $zipCodeText = $formObj->getTBRow('Zip', 'zip_code', $row['zip_code']);
        }

        $passwordRow = '';
        $emailRow = '';
        if ($cpCfg['m.core.staff.hasPasswordSalt']) {
            $has_pwd = '';
            $lblPassword = $ln->gd('m.core.staff.lbl.password', 'Password');
            if ($row['pass_word'] != '') {
                $has_pwd = 1;
                $lblPassword = $ln->gd('m.core.staff.lbl.changePassword', 'Change Password');
            }
            $passwordRow = "
            {$formObj->getTBRow($lblPassword, 'pass_word')}
            <input type='hidden' name='has_pwd' value='{$has_pwd}' />
            ";

            $exp = array('isEditable' => 0);

            $emailRow = "
            {$formObj->getTBRow($ln->gd('m.core.staff.lbl.email', 'Email'), 'email', $row['email'], $exp)}
            <input type='hidden' name='email' value='{$row['email']}' />
            ";

        } else {
            $passwordRow = "{$formObj->getTBRow($ln->gd('m.core.staff.lbl.password', 'Password'), 'pass_word', $row['pass_word'])}";
            $emailRow = $formObj->getTBRow('Email', 'email', $row['email'], $expNoedit);
        }
        
        $chngPwdNxtLogin = '';
        if($cpCfg['m.core.staff.hasChangePasswordNextLogin']){
            $chngPwdNxtLogin = $formObj->getYesNoRRow($ln->gd('m.core.staff.lbl.changePwdOnNext', 'Change password on next login'), 'change_password_next_login', $row['change_password_next_login']);
        }
        
        $commissionDetails = '';
        if($cpCfg['m.core.staff.hasCommissionDetails'] && $_SESSION['userGroupName'] == "Super Administrator"){

            $sqlCommType = $fn->getValueListSQL('commissionType');

            $commissionDetails = "
            {$formObj->getTBRow('Commission Rate', 'staff_commission_rate', $row['staff_commission_rate'])}
            {$formObj->getDDRowBySQL('Commission Type', 'commission_type', $sqlCommType, $row['commission_type'], $expVl)}
            ";
        }
        
        $fielset1 = "
        {$formObj->getTBRow($ln->gd('m.core.staff.lbl.firstName', 'First Name'), 'first_name', $row['first_name'])}
        {$formObj->getTBRow($ln->gd('m.core.staff.lbl.lastName', 'Last Name'), 'last_name', $row['last_name'])}
        {$fnModCountry->getCountryDropDown($formObj->mode, $row)}
        {$emailRow}
        {$passwordRow}
        {$formObj->getDDRowByArr($ln->gd('m.core.staff.lbl.status', 'Status'), 'status', $fnMod->getStaffStatusArray(), $row['status'])}
        {$userGrp}
        {$shortCode}
        {$staffTeam}
        {$staffType}
        {$sectionName}
        {$staffRate}
        {$sensDetails}
        {$formObj->getTBRow($ln->gd('m.core.staff.lbl.position', 'Position'), 'position', $row['position'])}
        {$formObj->getYesNoRRow($ln->gd('m.core.staff.lbl.published', 'Published'), 'published', $row['published'])}
        {$fn->getSiteDropDown($formObj->mode, $row)}
        {$chngPwdNxtLogin}
        {$commissionDetails} 
        ";

        $sqlCountry = getCPModelObj('common_geoCountry')->getCountryDDSQL();
        $expCountry = array('detailValue' => $row['country_title']);

        $fielset2 = "
        {$formObj->getTBRow($ln->gd('m.core.staff.lbl.streetAddress', 'Street Address'), 'address_street', $row['address_street'])}
        {$formObj->getTBRow($ln->gd('m.core.staff.lbl.town', 'Town / Suburb'), 'address_town', $row['address_town'])}
        {$formObj->getTBRow($ln->gd('m.core.staff.lbl.state', 'State'), 'address_state', $row['address_state'])}
        {$zipCodeText}
        {$formObj->getDDRowBySQL($ln->gd('m.core.staff.lbl.country', 'Country'), 'address_country', $sqlCountry, $row['address_country'], $expCountry)}
        {$description}
        ";

        if ($description != ''){
            $description = "
            {$formObj->getFieldSetWrapped($ln->gd('m.core.staff.lbl.description', 'Description'), $description )}
            ";
        }

        $text = "
        {$formObj->getFieldSetWrapped($ln->gd('m.core.staff.lbl.staffDetails', 'Staff Details'), $fielset1)}
        {$formObj->getFieldSetWrapped($ln->gd('m.core.staff.lbl.address', 'Address'), $fielset2)}
        {$description}
        {$formObj->getCreationModificationText($row)}
        ";

        return $text;
    }

    /**
     *
     */
    function getRightPanel($row) {
        $cpCfg = Zend_Registry::get('cpCfg');
        $displayLinkData = Zend_Registry::get('displayLinkData');
        $media = Zend_Registry::get('media');
        $ln = Zend_Registry::get('ln');
        
        $staffGroup = "";
        $signature  = "";
        $staffCommission  = "";
        $links = '';

        if ($cpCfg['m.core.hasStaffGroup'] == 1) {
            $staffGroup = $displayLinkData->getLinkPortalMain("core_staff", "project_staffGroupLink", $ln->gd('m.core.staff.link.staffGroupLinked', 'Staff Group Linked'), $row);
        }

        if ($cpCfg['cp.hasProjectMg'] == 1) {
            $signature = $media->getRightPanelMediaDisplay("Signature", "core_staff", $ln->gd('m.core.staff.link.signature', 'signature'), $row);
        }

        if ($cpCfg['m.core.staff.hasStaffCommission']) {
            $staffCommission = $displayLinkData->getLinkPortalMain("core_staff", "manPower_staffCommissionLink", "Staff Commission", $row);
        }

        $links .= $displayLinkData->getLinkPortalMain('core_staff', 'tradingsg_productGroupLink', 'Product Group Linked', $row);

        $text = "
        {$media->getRightPanelMediaDisplay($ln->gd('m.core.staff.link.picture', 'Picture'), "core_staff", "picture", $row)}
        {$signature}
        {$staffGroup}
        {$staffCommission}
        {$links}
        ";
        return $text;
    }
}