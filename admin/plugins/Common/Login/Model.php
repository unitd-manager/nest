<?
class CPL_Admin_Plugins_Common_Login_Model extends CP_Admin_Plugins_Common_Login_Model
{
    /**
     *
     */
    function getLoginSubmit() {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $dbUtil = Zend_Registry::get('dbUtil');

        $email     = $fn->getPostParam('email'    , '', true);
        $pass_word = $fn->getPostParam('pass_word', '', true);
        $saveLogin = $fn->getPostParam('saveLogin', '', true);
        $loginBySmartCard = $fn->getPostParam('loginBySmartCard', '', true);

        //-------------------------------------------------------------------------------------//
        $valArr   = $this->getLoginSubmitValidate();
        $hasError = $valArr[0];
        $xmlText  = $valArr[1];

        if ($hasError){
            header('Content-type: application/json');
            return $xmlText;
        }

        if ($loginBySmartCard){
            $smartCardId = $fn->getPostParam('smartCardId', '', true);
            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessStaffTable']}
            WHERE smart_card_id = '{$smartCardId}'
              AND published = 1
            ";
        } else {
            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessStaffTable']}
            WHERE email = '{$email}'
              AND published = 1
            ";
        }
        $result  = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);

        if ($numRows > 0) {
            $row = $db->sql_fetchrow($result);
            $userGroupId = $row['user_group_id'];

            //amended by syed for double login issue, not sure about the below condition
            //if($cpCfg['cp.hasMultiUsergroupPerStaff'] && $userGroupId != $cpCfg['cp.superAdminUGId']){
            if($cpCfg['cp.hasMultiUsergroupPerStaff']){
                $returnText = $this->view->getChooseUsergroupForm($row);
                return $validate->getSuccessMessageXML('', $returnText);
            } else {
                if($cpCfg['cp.captureAutoLogin']){
                    if ($row['developer'] != 1 && $row['staff_login_type'] != 'Agent') {

                        /* Checking Previous attendance */
                        $SQLAtt = "
                        SELECT * FROM {$cpCfg['cp.attendanceTable']}
                        WHERE staff_id = {$row['staff_id']}
                        ";
                        $resultAtt  = $db->sql_query($SQLAtt);
                        $numRowsAtt = $db->sql_numrows($resultAtt);

                        if ($numRowsAtt > 0) {
                            $this->getMarkPreviousAttendanceRecords($row);
                        }

                        $today = date('Y-m-d');
                        $SQLStaffAtt = "
                        SELECT *
                        FROM {$cpCfg['cp.attendanceTable']}
                        WHERE record_date = '{$today}'
                          AND staff_id = {$row['staff_id']}
                        ";
                        $resultStaffAtt  = $db->sql_query($SQLStaffAtt);
                        $numRowsStaffAtt = $db->sql_numrows($resultStaffAtt);

                        if ($numRowsStaffAtt == 0) {
                            $fa = array();

                            if ($cpCfg['cp.hasMultiUniqueSites']) {
                                $fa['site_id']      = $row['site_id'];
                            }

                            $fa['staff_id']         = $row['staff_id'];
                            $fa['record_date']      = $today;
                            $fa['time_in']          = date('H:i:s');
                            $fa['creation_date']    = date('Y-m-d H:i:s');
                            $fa['created_by']       = $row['first_name'] . ' ' . $row['last_name'];

                            $SQLInsertStaffAtt      = $dbUtil->getInsertSQLStringFromArray($fa, $cpCfg['cp.attendanceTable']);
                            $resultInsertStaffAtt   = $db->sql_query($SQLInsertStaffAtt);
                            $hist_id                = $cpCfg['cp.attendanceTable'] . '_id';
                            $hist_id                = $db->sql_nextid();
                        }
                    }
                }

                $updateLoggedin = "
                UPDATE staff
                SET logged_in_status = 'Active'
                WHERE staff_id = {$row['staff_id']}
                ";
                $resultLoggedin = $db->sql_query($updateLoggedin);

                $retUrl = $this->setSessionValuesAfterLogin($row, $saveLogin);
                /** if there is a hook for homepage in the theme level then use that **/
                $theme = getCPThemeObj($cpCfg['cp.theme']);
                if (method_exists($theme->fns, 'setSessionValuesAfterLogin')){
                    $theme->fns->setSessionValuesAfterLogin($row);
                }
                return $validate->getSuccessMessageXML($retUrl);
            }
        }
    }

    /**
     *
     */
    function getMarkPreviousAttendanceRecords($row) {
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

        /* Finding last attendance record */
        $sqlPrevAtt = "
        SELECT MAX(record_date) AS last_attendance_date FROM {$cpCfg['cp.attendanceTable']}
        WHERE staff_id = {$row['staff_id']}
        ";
        $resultPrevAtt  = $db->sql_query($sqlPrevAtt);
        $rowPrevAtt     = $db->sql_fetchrow($resultPrevAtt);

        $date = new DateTime($rowPrevAtt['last_attendance_date']);
        $date->modify('+1 day');
        $begin = $date->format('Y-m-d');
        $end   = date("Y-m-d",mktime (0,0,0,date("m"),date("d"), date("Y")));

        $begin = new DateTime($begin);
        $end   = new DateTime($end);

        $interval = array();
        //Create array with all dates within date span
    	while($begin < $end) {
    		$interval[] = $begin->format('Y-m-d');
    		$begin->modify('+1 day');
    	}

	    #$interval = new DateInterval('P1D');
        #$daterange = new DatePeriod($begin, $interval ,$end);

        foreach($interval as $date){

            #$record_date = $date->format("Y-m-d");
            $timestamp   = strtotime($date);
            $record_day  = date("D", $timestamp);

            // Inserting attendance record excluding Sunday
            if ($record_day != 'Sun') {
                $fa = array();
                $fa['staff_id']         = $row['staff_id'];
                $fa['record_date']      = $date;
                $fa['on_leave']         = 1;
                $fa['creation_date']    = date('Y-m-d H:i:s');
                $fa['created_by']       = $row['first_name'] . ' ' . $row['last_name'];

                $SQLInsertStaffAtt      = $dbUtil->getInsertSQLStringFromArray($fa, $cpCfg['cp.attendanceTable']);
                $resultInsertStaffAtt   = $db->sql_query($SQLInsertStaffAtt);
                $hist_id                = $cpCfg['cp.attendanceTable'] . '_id';
                $hist_id                = $db->sql_nextid();
            }
        }
    }

    /**
     *
     */
    function getLoginSubmitValidate() {
        $ln = Zend_Registry::get('ln');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');

        $validate->resetErrorArray();

        $text  = "";

        $loginBySmartCard = $fn->getPostParam('loginBySmartCard', '', true);

        if ($loginBySmartCard){
            $smartCardId = $fn->getPostParam('smartCardId', '', true);
            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessStaffTable']}
            WHERE smart_card_id = '{$smartCardId}'
              AND published = 1
            ";
            $result  = $db->sql_query($SQL);
            $numRows = $db->sql_numrows($result);
            if ($numRows == 0) {
                $validate->errorArray['smart_card_id']['name'] = "smart_card_id";
                $validate->errorArray['smart_card_id']['msg']  = 'Invalid Card';
            }

        } else {
            $isEmailInvalidFormat    = $validate->validateData("email", $ln->gd("cp.form.fld.email.err"), "email", "", "3", "50");
            $isPasswordInvalidFormat = $validate->validateData("pass_word", $ln->gd("cp.form.fld.password.err"), "empty", "", "3", "20" );

            if(!$isEmailInvalidFormat && !$isPasswordInvalidFormat){
                $email     = $fn->getPostParam('email', '', true);
                $pass_word = $fn->getPostParam('pass_word', '', true);
                $staffRow = $this->checkLogin($email, $pass_word);

                /*if($pass_word != "" && $pass_word != "") {
                    $SQLLogginCheck = "
                    SELECT  logged_in_status 
                    FROM staff
                    WHERE email = '{$email}'
                      AND published = 1
                      AND developer != 1
                    ";
                    $resultLogginCheck  = $db->sql_query($SQLLogginCheck);
                    $rowLogginCheck     = $db->sql_fetchrow($resultLogginCheck);

                    if($rowLogginCheck['logged_in_status'] == "Active"){
                        $validate->errorArray['email']['name'] = "email";
                        $validate->errorArray['email']['msg'] = "Please Log Out From Other Device and Try to Login Again!";
                    }
                }*/

                if(!$staffRow) {
                    $validate->errorArray['email']['name'] = "email";
                    $validate->errorArray['email']['msg']  = $ln->gd("p.member.login.form.err.invalidLogin");
                    $validate->errorArray['pass_word']['name'] = "pass_word";
                    $validate->errorArray['pass_word']['msg']  = "";
                } else {
                    if ($staffRow['published'] == 0 && $staffRow['activated'] == 0){
                        $validate->errorArray['email']['name'] = "email";
                        $validate->errorArray['email']['msg']  = $ln->gd("accountNotActivatedError");
                    } else if ($staffRow['published'] == 0){
                        $validate->errorArray['email']['name'] = "email";
                        $validate->errorArray['email']['msg']  = $ln->gd("p.member.login.form.err.invalidLogin");

                    } else {
                        if ($cpCfg['cp.hasMultiUniqueSites'] && $staffRow['site_id'] == '' && $staffRow['developer'] == 0){
                            $validate->errorArray['email']['name'] = "email";
                            $validate->errorArray['email']['msg'] = $ln->gd("p.member.login.form.err.noSiteLinked");
                        }
                    }
                }
            }
        }

        if (count($validate->errorArray) == 0){
            return array(0, $validate->getSuccessMessageXML());
        } else {
            $fn->resetCookie("adminUserNameC");
            $fn->resetCookie("adminPasswordC");
            return array(1, $validate->getErrorMessageXML());
        }

        return $text;
    }

    /**
     *
     * @param type $email
     * @param type $pass_word
     * @return type
     */
    function checkLogin($email, $pass_word) {
        $db = Zend_Registry::get('db');
        $cpCfg  = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');

        $loginSuccess = false;
        $staffRow = null;
        if ($cpCfg['m.core.staff.hasPasswordSalt']) {
            //get the staff record
            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessStaffTable']}
            WHERE email = '{$email}'
              AND published = 1
            ";
            $result  = $db->sql_query($SQL);
            $numRows = $db->sql_numrows($result);
            if ($numRows > 0) {
                $row = $db->sql_fetchrow($result);
                $auth_pass = $cpUtil->getSaltedPassword($email, $pass_word, $row['salt']);

                $SQL = "
                SELECT *
                FROM {$cpCfg['cp.modAccessStaffTable']}
                WHERE email = '{$email}'
                  AND pass_word = '{$auth_pass}'
                  AND published = 1
                ";
                $result  = $db->sql_query($SQL);
                $numRows = $db->sql_numrows($result);

                if ($numRows > 0) {
                    $loginSuccess = true;
                    $staffRow = $db->sql_fetchrow($result);
                }
            }

        } else {
            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessStaffTable']}
            WHERE email = '{$email}'
              AND pass_word = '{$pass_word}'
              AND published = 1
            ";
            $result  = $db->sql_query($SQL);
            $numRows = $db->sql_numrows($result);
            if ($numRows > 0) {
                $loginSuccess = true;
                $staffRow = $db->sql_fetchrow($result);
            }
        }

        return $staffRow;
    }

    /**
     *
     */
    function setSessionValuesAfterLogin($row, $saveLogin) {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');

        $fn->sessionRegenerate();

        $userGroupName = '';
        $userGroupType = '';

        if ($row['user_group_id'] != ''){
            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessUserGroupTable']}
            WHERE user_group_id = {$row['user_group_id']}
            ";
            $result  = $db->sql_query($SQL);
            $rowUserGroup = $db->sql_fetchrow($result);

            $userGroupName = $rowUserGroup['title'];
            $userGroupType = @$rowUserGroup['user_group_type'];
        }
        $_SESSION['userGroupID']   = $row['user_group_id'];
        $_SESSION['userGroupName'] = $userGroupName;
        $_SESSION['userGroupType'] = $userGroupType;

        if(isset($row['address_country'])){
            $_SESSION['staffGeoCountryCode']= $row['address_country'];
        }

        $_SESSION['staff_id']        = $row[$cpCfg['cp.modAccessStaffIdLabel']];
        $_SESSION['userFullName']    = $row['first_name'] . " " . $row['last_name'];
        $_SESSION['isLoggedInAdmin'] = true;
        $_SESSION['isDeveloper']     = @$row['developer'];
        $_SESSION['userName']        = $_SESSION['userFullName'];

        if ($cpCfg['cp.hasMultiUniqueSites']){
            $_SESSION['cp_site_id'] = @$row['site_id'];
        }

        if ($cpCfg['m.core.staff.hasChangePasswordNextLogin']){
            $_SESSION['changePasswordOnLogin'] = $row['change_password_next_login'];
        }

        if ($cpCfg['cp.hasProjectMg'] == 1) {
            $_SESSION['staff_type']           = $row['staff_type'];
            $_SESSION['showSensitiveDetails'] = $row['show_sensitive_details'];
            $_SESSION['staff_team']           = $row['team'];
            $_SESSION['email']                = $row['email'];
            $_SESSION['isLoggedInWWW']        = 1;
            $_SESSION['contact_id']           = $row['staff_id'];
            $_SESSION['userFullNameWWW']      = $row['first_name'] . " " . $row['last_name'];
        }

        if ($cpCfg['cp.hasFirstRoomValueInStaff'] == 1) {
            $_SESSION['sectionName'] = $row['section_name'];
        }

        if ($saveLogin == "1") {
            $fn->setCookie("adminUserNameC", $row['email']);
            $fn->setCookie("adminPasswordC", $row['pass_word']);
        } else {
            $fn->resetCookie("adminUserNameC");
            $fn->resetCookie("adminPasswordC");
        }

        $randomIDText = "";

        if ($cpCfg['cp.autoLoginToIntranet'] == 1) {
            $urlRand = $cpCfg['intranetUrl'] . "index.php?_spAction=autoLoginRandomID&showHTML=0&user_name={$row['email']}";

            require_once 'HTTP/Request2.php';

            $request = new HTTP_Request2($urlRand, HTTP_Request2::METHOD_GET);
            try {
                $response = $request->send();
                if (200 == $response->getStatus()) {
                    $random_id = $response->getBody();
                    $randomIDText = "&random_id={$random_id}";
                } else {
                    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
                         $response->getReasonPhrase();
                }
            } catch (HTTP_Request2_Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        $retUrl = '';
        if (@$_SESSION['returnUrlAfterLogin'] != ''){
            $retUrl = $_SESSION['returnUrlAfterLogin'];
        } else {
            $retUrl = '';
        }

        if ($retUrl != "") {
            $retUrl .= (strpos($retUrl , '?') === false) ? "?" : "&";
            $retUrl .= "logged_in=1{$randomIDText}";
        } else {
            $retUrl = "index.php?logged_in=1{$randomIDText}";
        }

        unset($_SESSION['returnUrlAfterLogin']);

        return $retUrl;

    }

    /**
     *
     * @return <type>
     */
    function loginWithCookie() {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $text  = "";

        if (!$cpCfg['cp.adminHasRememberLogin']) {
            return;
        }
        $returnUrl      = $fn->getPostParam('returnUrl');
        $adminUserNameC = $fn->getCookieParam('adminUserNameC');
        $adminPasswordC = $fn->getCookieParam('adminPasswordC');

        //-----------------------------------------------------------------//
        if ($adminUserNameC == "" && $adminPasswordC == ""){
            return;

        } else {
            $email = $adminUserNameC;
            $pass_word = $adminPasswordC;

            $SQL = "
            SELECT *
            FROM {$cpCfg['cp.modAccessStaffTable']}
            WHERE email = '{$email}'
              AND pass_word = '{$pass_word}'
              AND published = 1
            ";

            $result  = $db->sql_query($SQL);
            $numRows = $db->sql_numrows($result);

            if ($numRows > 0) {
                $row = $db->sql_fetchrow($result);

                if ($cpCfg['cp.hasMultiUniqueSites'] && $row['site_id'] == '' && $row['developer'] == 0){
                    $fn->resetCookie("adminUserNameC", "", time()-1209600);
                    $fn->resetCookie("adminPasswordC", "", time()-1209600);
                } else {
                    $_SESSION['returnUrlAfterLogin'] = $_SERVER['REQUEST_URI'];
                    $retUrl = $this->setSessionValuesAfterLogin($row, 1);
                    $cpUtil->redirect($retUrl);
                }

            } else {
                $fn->resetCookie("adminUserNameC");
                $fn->resetCookie("adminPasswordC");
            }
        }
    }

    //==================================================================//
    function getAutoLoginRandomID() {
        $dbUtil = Zend_Registry::get('dbUtil');
        $tv = Zend_Registry::get('tv');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $cpUtil = Zend_Registry::get('cpUtil');

        $user_name = $fn->getReqParam('user_name');
        $random_id = $cpUtil->getRandomNumber();

        $fa = array();

        $fa['user_name'] = $user_name;
        $fa['random_id'] = $random_id;

        $SQL         = $dbUtil->getInsertSQLStringFromArray($fa, "auto_login");
        $result      = $db->sql_query($SQL);
        print $random_id;
    }

    //==================================================================//
    function getAutoLoginUserByRandomID() {
        $dbUtil = Zend_Registry::get('dbUtil');
        $tv = Zend_Registry::get('tv');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $cpUtil = Zend_Registry::get('cpUtil');

        $random_id = $fn->getReqParam('random_id', '', true);
        $rec = $fn->getRecordByCondition('auto_login', "random_id='{$random_id}'");

        if (isset($rec['user_name'])) {
            $user_name = $rec['user_name'];

            $user = $fn->getRecordByCondition($cpCfg['cp.modAccessStaffTable'], "user_name='{$user_name}'");

            if (!isset($user['user_name'])) {
                return;
            }

            if (class_exists('LoginLocal')) {
                $login = new LoginLocal();
            } else {
                $login = new Login();
            }

            $login->loginSubmit($user['user_name'], $user['pass_word'], '', '', 0);

            $SQL    = "DELETE FROM auto_login WHERE random_id = '{random_id}'";
            $result = $db->sql_query($SQL);
        }
    }

    /**
     *
     */
    function getChooseUsergroupSubmit() {
        $hook = getCPPluginHook('common_login', 'chooseUsergroupSubmit', $this);
        if($hook['status']){
            return $hook['html'];
        }

        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');

        $user_group_id = $fn->getPostParam('user_group_id', '', true);
        $staffId       = $fn->getPostParam('staff_id', '', true);
        $saveLogin     = $fn->getPostParam('saveLogin', '', true);
        $count = $fn->getRecordCount('mod_acc_shop_user_group', "staff_id = '{$staffId}' AND user_group_id = '{$user_group_id}'");
        if ($count > 0){
            $row = $fn->getRecordRowByID($cpCfg['cp.modAccessStaffTable'], 'staff_id', $staffId, array('condn' => 'AND published = 1'));
            $row['user_group_id'] = $user_group_id;

            $retUrl = $this->setSessionValuesAfterLogin($row, $saveLogin);
            /** if there is a hook for homepage in the theme level then use that **/
            $theme = getCPThemeObj($cpCfg['cp.theme']);
            if (method_exists($theme->fns, 'setSessionValuesAfterLogin')){
                $theme->fns->setSessionValuesAfterLogin($row);
            }
            return $validate->getSuccessMessageXML($retUrl);
        } {
            return $validate->getSuccessMessageXML("index.php");
        }
    }

    /**
     *
     */
    function getLogout() {
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $db = Zend_Registry::get('db');
        $dbUtil = Zend_Registry::get('dbUtil');

        if($cpCfg['cp.captureAutoLogin']){
            $today = date('Y-m-d');

            $SQLStaffAtt = "
            SELECT *
            FROM {$cpCfg['cp.attendanceTable']}
            WHERE record_date = '{$today}'
              AND staff_id = '{$_SESSION['staff_id']}'
            ";
            $resultStaffAtt  = $db->sql_query($SQLStaffAtt);
            $numRowsStaffAtt = $db->sql_numrows($resultStaffAtt);

            if ($numRowsStaffAtt > 0) {
                $fa = array();

                $fa['leave_time']           = date('H:i:s');
                $fa['modification_date']    = date('Y-m-d H:i:s');
                $fa['modified_by']          = $_SESSION['userFullName'];

                $whereCondition = "WHERE record_date = '{$today}' AND staff_id = '{$_SESSION['staff_id']}'";
                $SQL = $dbUtil->getUpdateSQLStringFromArray($fa, $cpCfg['cp.attendanceTable'], $whereCondition);
                $db->sql_query($SQL);
            }
        }

        $updateLoggedin = "
        UPDATE staff
        SET logged_in_status = 'Closed'
        WHERE staff_id = {$_SESSION['staff_id']}
        ";
        $resultLoggedin = $db->sql_query($updateLoggedin);

        session_destroy();
        $fn->resetCookie("adminUserNameC");
        $fn->resetCookie("adminPasswordC");
        $fn->sessionRegenerate();

        // added the below 2 lines by ahmad due to bug with resetCookie function
        setcookie('adminUserNameC', '',  time()-1209600);
        setcookie('adminPasswordC', '',  time()-1209600);

        $cpUtil->redirect('index.php');
    }

    /**
     *
     */
    function getInstallationFormSubmit() {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $dbUtil = Zend_Registry::get('dbUtil');


        if (!$this->getInstallationFormValidate()){
            return $validate->getErrorMessageXML();
        }

        $this->getResetDBForInstallation();

        $product_key  = $fn->getPostParam('product_key');
        $company_name = $fn->getPostParam('company_name');
        $address1     = $fn->getPostParam('address1');
        $address2     = $fn->getPostParam('address2');
        $address3     = $fn->getPostParam('address3');
        $gst_no       = $fn->getPostParam('gst_no');
        $admin_email  = $fn->getPostParam('admin_email');
        $mobile_no    = $fn->getPostParam('mobile_no');
        $landline_no  = $fn->getPostParam('landline_no');
        $agent_code   = $fn->getPostParam('agent_code');

        $data = $this->getProductKeySubmit($address1, $address2, $address3, $gst_no, $mobile_no, $landline_no, $admin_email, $product_key, $agent_code);
        $no_of_users = $data["no_of_users"];
        $no_of_days  = $data["no_of_days"];
 
        $companypass = preg_replace('/\s+/', '', $company_name);
        $companypass = substr($companypass, 0, 3);
        $companypass = strtolower($companypass);
        $AdminPass   = mt_rand(10000, 99999);

        $fa = array();
        $fa['user_group_id'] = 8;
        $fa['email']         = "admin@cubobillpro.com";
        $fa['published']     = 1;
        $fa['creation_date'] = date("Y-m-d H:i:s");
        $fa['user_name']     = "cuboadmin";
        $fa['pass_word']     = $companypass.$AdminPass;
        $fa['first_name']    = "BillPro";
        $fa['last_name']     = "Admin";
        $fa['status']        = "Current";
        $fa['developer']     = 0;

        $insertAdminStaffSql = $dbUtil->getInsertSQLStringFromArray($fa, 'staff');
        $resultAdminStaffSQL = $db->sql_query($insertAdminStaffSql);

        for($i = 1; $i <= $no_of_users; $i++){
            $CounterPass = mt_rand(10000, 99999);

            $fa1 = array();
            $fa1['user_group_id']  = 6;
            $fa1['email']          = "counter".$i."@cubobillpro.com";
            $fa1['published']      = 1;
            $fa1['creation_date']  = date("Y-m-d H:i:s");
            $fa1['user_name']      = "counter".$i;
            $fa1['pass_word']      = $companypass.$CounterPass;
            $fa1['first_name']     = "Counter".$i;
            $fa1['last_name']      = "User";
            $fa1['status']         = "Current";  
            $fa1['developer']      = 0;

            $insertCounterStaffSql = $dbUtil->getInsertSQLStringFromArray($fa1, 'staff');
            $resultCounterStaffSQL = $db->sql_query($insertCounterStaffSql);
        }

        $fa2 = array();
        $fa2['trial_key']   = $product_key;
        $fa2['status']      = "Trial";
        $fa2['agent_code']  = $agent_code;
        $fa2['no_of_users'] = $no_of_users;
        $fa2['no_of_days']  = $no_of_days;
        $fa2['active_date'] = date("Y-m-d");
        $fa2['expiry_date'] = date('Y-m-d', strtotime("+".$no_of_days." days"));

        $insertActivation = $dbUtil->getInsertSQLStringFromArray($fa2, 'lead_history');
        $resultActivation = $db->sql_query($insertActivation);

        $SQLAddress1 = "
        UPDATE setting
        SET  value = '{$address1}'
        WHERE key_text = 'cp.addressPdf1'
        ";
        $resultAddress1  = $db->sql_query($SQLAddress1);

        $SQLAddress2 = "
        UPDATE setting
        SET  value = '{$address2}'
        WHERE key_text = 'cp.addressPdf2'
        ";
        $resultAddress2  = $db->sql_query($SQLAddress2);

        $SQLAddress3 = "
        UPDATE setting
        SET  value = '{$address3}'
        WHERE key_text = 'cp.addressPdf3'
        ";
        $resultAddress3  = $db->sql_query($SQLAddress3);

        $SQLAddress4 = "
        UPDATE setting
        SET  value = 'Phone No: {$landline_no}'
        WHERE key_text = 'cp.addressPdf4'
        ";
        $resultAddress4  = $db->sql_query($SQLAddress4);

        $SQLAddress5 = "
        UPDATE setting
        SET  value = 'GST Reg. NO: {$gst_no}'
        WHERE key_text = 'cp.addressPdf5'
        ";
        $resultAddress5  = $db->sql_query($SQLAddress5);

        $SQLProductKey = "
        UPDATE setting
        SET  value = '{$product_key}'
        WHERE key_text = 'cp.serialKeyActive'
        ";
        $resultProductKey  = $db->sql_query($SQLProductKey);

        $SQLCompanyName = "
        UPDATE setting
        SET  value = '{$company_name}'
        WHERE key_text = 'cp.companyName'
        ";
        $resultCompanyName  = $db->sql_query($SQLCompanyName);

        if($admin_email != ""){
            $SQLEmail = "
            UPDATE setting
            SET  value = '{$admin_email}'
            WHERE key_text = 'cp.adminEmail'
            ";
            $resultEmail  = $db->sql_query($SQLEmail);
        }

        $SQLActivation = "
        UPDATE setting
        SET  value = 'Activated'
        WHERE key_text = 'cp.activationStatus'
        ";
        $resultActivation = $db->sql_query($SQLActivation);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getInstallationFormValidate() {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $validate = Zend_Registry::get('validate');
        $dbUtil = Zend_Registry::get('dbUtil');

        $product_key = $fn->getPostParam('product_key');

        $validate->resetErrorArray();
        $validate->validateData('product_key', 'Please Enter The Product Key');
        $validate->validateData('company_name', 'Please Enter The Company Name');
        $validate->validateData('address1', 'Please Enter The D.No, Address Street');
        $validate->validateData('address2', 'Please Enter The Address Area');
        $validate->validateData('address3', 'Please Enter The City - Pincode');
        $validate->validateData('gst_no', 'Please Enter The GST Number');
        $validate->validateData('agent_code', 'Please Enter The Agent Code');
        //$validate->validateData('admin_email', 'Please Enter The Email');
        $validate->validateData('mobile_no', 'Please Enter The Mobile Number');
        $validate->validateData('landline_no', 'Please Enter The Landline Number');

        if($product_key != ""){
            $data = $this->getProductKeyValidate($product_key);
            $resultProductKeyCheck = $data["status"];

            if($resultProductKeyCheck != "Success"){
                $validate->errorArray['product_key']['name'] = "product_key";
                $validate->errorArray['product_key']['msg']  = "Please Enter Valid Product Key";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     */
    function getProductKeyValidate($product_key) {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $url = 'http://cubosalecrm.cubosale.in/devapp/CheckProductKey.php';

        $fields = array(
            'trial_key'      => $product_key
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $resultProductKeyCheck = curl_exec($ch);
        $data = json_decode($resultProductKeyCheck, true);

        //close connection
        curl_close($ch);

        return $data;
    }

    /**
     *
     */
    function getProductKeySubmit($address1, $address2, $address3, $gst_no, $mobile_no, $landline_no, $admin_email, $product_key, $agent_code) {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $url = 'http://cubosalecrm.cubosale.in/devapp/register.php';

        $fields = array(
            'trial_key'   => $product_key,
            'address1'    => $address1,
            'address2'    => $address2,
            'address3'    => $address3,
            'gst_no'      => $gst_no,
            'mobile_no'   => $mobile_no,
            'landline_no' => $landline_no,
            'admin_email' => $admin_email,
            'agent_code'  => $agent_code
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $resultProductKeyCheck = curl_exec($ch);
        $data = json_decode($resultProductKeyCheck, true);

        //close connection
        curl_close($ch);

        return $data;
    }

    /**
     *
     */
    function getPrintLoginDetails() {
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $searchVar = Zend_Registry::get('searchVar');
        $media = Zend_Registry::get('media');
        $cpPaths = Zend_Registry::get('cpPaths');
        $dbUtil = Zend_Registry::get('dbUtil');

        ini_set('memory_limit', '512M');

        set_time_limit(50000);

        include_once(CP_LIBRARY_PATH.'lib_php/tcpdf/tcpdf.php');
        include_once(CP_LOCAL_PATH.'lib/headfoot.php');

        $pdf = new MYPDF_Local(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Cubo BillPro');
        $pdf->SetTitle('Cubo BillPro');
        $pdf->SetSubject('Login Details');
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 04', PDF_HEADER_STRING);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER,10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        $pdf->SetFont('Courier','B',10);
        $pdf->AddPage();


        $SQLStaff = "
        SELECT email 
              ,pass_word
        FROM staff
        WHERE status = 'Current'
        AND user_group_id IN ('8', '6')
        AND developer != 1
        ORDER BY staff_id ASC
         ";

        $resultStaff  = $db->sql_query($SQLStaff);
        $numRowsStaff = $db->sql_numrows($resultStaff);

        $tblHeader = '<table border="0" cellpadding="4" width="100%">
                        <tr>
                            <th style="font-size:18px;">Login Details:</th>
                        </tr>
                      </table>';

        $tbl1 ='<table border="1" cellpadding="8" width="100%">';

        $tbl1 = $tbl1.'
                <thead>
                    <tr bgcolor="#49C3E9">
                        <th style="color:#FFFFFF;">Username</th>
                        <th style="color:#FFFFFF;">Password</th>
                    </tr>
                </thead>
                <tbody>';

        while ($rowStaff = $db->sql_fetchrow($resultStaff)) {
            $tbl1 = $tbl1.'
                    <tr nobr="true">
                        <td>'.$rowStaff['email'].'</td>
                        <td>'.$rowStaff['pass_word'].'</td>
                    </tr>';
        }

        $tbl1 = $tbl1.'</tbody></table>';

        $pdf->writeHTML($tblHeader, true, false, false, false, '');
        $pdf->writeHTML($tbl1, true, false, false, false, '');
        $pdf->Output('purchase_order.pdf', 'I');

    }

    /**
     *
     */
    function getUnpublishLoginForExpired() {
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');

        $SQLStaff = "
        UPDATE staff SET published = 0
        WHERE user_group_id IN ('8', '6')
        AND developer != 1
         ";
        $resultStaff  = $db->sql_query($SQLStaff);
    }


    /**
     *
     */
    function getUpdateTrialToFullVersion(){
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $cpUtil = Zend_Registry::get('cpUtil');
        $formObj = Zend_Registry::get('formObj');
        $formAction = "index.php?plugin=common_login&_spAction=UpdateTrialToFullVersionSubmit&showHTML=0";

        $expLimit = array('charsLimit' => 23);
        $text = "
        <form id='ProductKeyActivateForm' class='ProductKeyActivateForm yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTBRow('Product Key', 'active_key', '', $expLimit)}
        </form>
        ";

        return $text;
    }

    /**
     *
     */
    function getUpdateTrialToFullVersionValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $validate->validateData('active_key', 'Please Enter Product To Activate');

        $active_key = $fn->getPostParam('active_key');

        if($active_key != ""){
            $data = $this->getProductActiveKeyValidate($active_key);
            $resultProductKeyCheck = $data["status"];

            if($resultProductKeyCheck != "Success"){
                $validate->errorArray['active_key']['name'] = "active_key";
                $validate->errorArray['active_key']['msg']  = "Please Enter Valid Product Key";
            }
        }

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getUpdateTrialToFullVersionSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getUpdateTrialToFullVersionValidate()){
            return $validate->getErrorMessageXML();
        }

        $active_key = $fn->getPostParam('active_key');

        $data = $this->getProductActiveKeySubmit($active_key);

        $fa = array();
        $fa['active_key']  = $active_key;
        $fa['status']      = "Active";
        $fa['expiry_date'] = "";

        $whereCondition = "WHERE status = 'Trial'";
        $SQL    = $dbUtil->getUpdateSQLStringFromArray($fa, "lead_history", $whereCondition);
        $result = $db->sql_query($SQL);

        $SQLStaff = "
        UPDATE staff SET published = 1
        WHERE user_group_id IN ('8', '6')
         ";
        $resultStaff  = $db->sql_query($SQLStaff);

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getProductActiveKeyValidate($active_key) {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $url = 'http://cubosalecrm.cubosale.in/devapp/CheckProductKeyActive.php';

        $active_key = $fn->getPostParam('active_key');

        $fields = array(
            'active_key'      => $active_key
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $resultProductKeyCheck = curl_exec($ch);
        $data = json_decode($resultProductKeyCheck, true);

        //close connection
        curl_close($ch);

        return $data;
    }

    /**
     *
     */
    function getProductActiveKeySubmit($active_key) {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $url = 'http://cubosalecrm.cubosale.in/devapp/registerActive.php';

        $fields = array(
            'active_key' => $active_key,
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $resultProductKeyCheck = curl_exec($ch);
        $data = json_decode($resultProductKeyCheck, true);

        //close connection
        curl_close($ch);

        return $data;
    }

    /**
     *
     */
    function getChangeToCustomerVersion(){
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $SQLActivationStatus = "
        UPDATE setting
        SET  value = ''
        WHERE key_text = 'cp.activationStatus'
        ";
        $resultActivationStatus  = $db->sql_query($SQLActivationStatus);

        $SQLSerialKey = "
        UPDATE setting
        SET  value = ''
        WHERE key_text = 'cp.serialKeyActive'
        ";
        $resultSerialKey = $db->sql_query($SQLSerialKey);

        $SQLRmDemVer = "
        UPDATE setting
        SET  value = '0'
        WHERE key_text = 'cp.demoVersionStatus'
        ";
        $resultRmDemVer = $db->sql_query($SQLRmDemVer);

    }


    /**
     *
     */
    function getChangeToDemoVersion(){
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $SQLActivationStatus = "
        UPDATE setting
        SET  value = 'Activated'
        WHERE key_text = 'cp.activationStatus'
        ";
        $resultActivationStatus  = $db->sql_query($SQLActivationStatus);

        $SQLRmDemVer = "
        UPDATE setting
        SET  value = '1'
        WHERE key_text = 'cp.demoVersionStatus'
        ";
        $resultRmDemVer = $db->sql_query($SQLRmDemVer);

    }

    /**
     *
     */
    function getResetDBForInstallation(){
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $SQLStaff = "
        DELETE FROM `staff`
        WHERE developer != 1
        ";
        $resultStaff  = $db->sql_query($SQLStaff);

        $SQLQuoteCode = "
        UPDATE setting
        SET  value = '1001'
        WHERE key_text = 'nextQuoteCode'
        ";
        $resultQuoteCode  = $db->sql_query($SQLQuoteCode);

        $SQLPoCode = "
        UPDATE setting
        SET  value = '1001'
        WHERE key_text = 'poCode'
        ";
        $resultPoCode  = $db->sql_query($SQLPoCode);

        $SQLInvoiceCode = "
        UPDATE setting
        SET  value = '10001'
        WHERE key_text = 'nextInvoiceCode'
        ";
        $resultInvoiceCode  = $db->sql_query($SQLInvoiceCode);

        $SQLReceiptCode = "
        UPDATE setting
        SET  value = '1001'
        WHERE key_text = 'nextReceiptCode'
        ";
        $resultReceiptCode  = $db->sql_query($SQLReceiptCode);

        $SQLEnquiryCode = "
        UPDATE setting
        SET  value = '1001'
        WHERE key_text = 'nextEnquiryCode'
        ";
        $resultEnquiryCode  = $db->sql_query($SQLEnquiryCode);

        $SQLProdItemCode = "
        UPDATE setting
        SET  value = '10001'
        WHERE key_text = 'nextProductItemCode'
        ";
        $resultProdItemCode  = $db->sql_query($SQLProdItemCode);

        $SQLLeadsCode = "
        UPDATE setting
        SET  value = '101'
        WHERE key_text = 'nextLeadsCode'
        ";
        $resultLeadsCode  = $db->sql_query($SQLLeadsCode);

        $SQLSoCode = "
        UPDATE setting
        SET  value = '1001'
        WHERE key_text = 'nextSoCode'
        ";
        $resultSoCode  = $db->sql_query($SQLSoCode);

        $SQLPOSBillCode = "
        UPDATE setting
        SET  value = '1'
        WHERE key_text = 'nextBillNumber'
        ";
        $resultPOSBillCode  = $db->sql_query($SQLPOSBillCode);

        $SQLPurchaseOrderCode = "
        UPDATE setting
        SET  value = '10000'
        WHERE key_text = 'nextPurchaseOrderCode'
        ";
        $resultPurchaseOrderCode  = $db->sql_query($SQLPurchaseOrderCode);

        $SQLInventoryCode = "
        UPDATE setting
        SET  value = '1000'
        WHERE key_text = 'inventoryCode'
        ";
        $resultInventoryCode  = $db->sql_query($SQLInventoryCode);

        $SQLEmptyTables1 = "
        TRUNCATE `call_registry`
        ";
        $resultEmptyTables1  = $db->sql_query($SQLEmptyTables1);
        
        $SQLEmptyTables2 = "
        TRUNCATE `category`
        ";
        $resultEmptyTables2  = $db->sql_query($SQLEmptyTables2);

        $SQLEmptyTables3 = "
        TRUNCATE `comment`
        ";
        $resultEmptyTables3  = $db->sql_query($SQLEmptyTables3);

        $SQLEmptyTables4 = "
        TRUNCATE `company`
        ";
        $resultEmptyTables4  = $db->sql_query($SQLEmptyTables4);

        $SQLEmptyTables5 = "
        TRUNCATE `contact`
        ";
        $resultEmptyTables5  = $db->sql_query($SQLEmptyTables5);

        $SQLEmptyTables6 = "
        TRUNCATE `discount_finance`
        ";
        $resultEmptyTables6  = $db->sql_query($SQLEmptyTables6);

        $SQLEmptyTables7 = "
        TRUNCATE `enquiry`
        ";
        $resultEmptyTables7  = $db->sql_query($SQLEmptyTables7);

        $SQLEmptyTables8 = "
        TRUNCATE `enquiry_quote`
        ";
        $resultEmptyTables8  = $db->sql_query($SQLEmptyTables8);

        $SQLEmptyTables9 = "
        TRUNCATE `inventory`
        ";
        $resultEmptyTables9  = $db->sql_query($SQLEmptyTables9);

        $SQLEmptyTables10 = "
        TRUNCATE `invoice`
        ";
        $resultEmptyTables10  = $db->sql_query($SQLEmptyTables10);

        $SQLEmptyTables11 = "
        TRUNCATE `invoice_item`
        ";
        $resultEmptyTables11  = $db->sql_query($SQLEmptyTables11);

        $SQLEmptyTables12 = "
        TRUNCATE `invoice_receipt_history`
        ";
        $resultEmptyTables12  = $db->sql_query($SQLEmptyTables12);

        $SQLEmptyTables13 = "
        TRUNCATE `lead_history`
        ";
        $resultEmptyTables13  = $db->sql_query($SQLEmptyTables13);

        $SQLEmptyTables14 = "
        TRUNCATE `order`
        ";
        $resultEmptyTables14  = $db->sql_query($SQLEmptyTables14);

        $SQLEmptyTables15 = "
        TRUNCATE `order_item`
        ";
        $resultEmptyTables15  = $db->sql_query($SQLEmptyTables15);

        $SQLEmptyTables16 = "
        TRUNCATE `po_product`
        ";
        $resultEmptyTables16  = $db->sql_query($SQLEmptyTables16);

        $SQLEmptyTables17 = "
        TRUNCATE `product`
        ";
        $resultEmptyTables17  = $db->sql_query($SQLEmptyTables17);

        $SQLEmptyTables18 = "
        TRUNCATE `media`
        ";
        $resultEmptyTables18  = $db->sql_query($SQLEmptyTables18);

        $SQLEmptyTables19 = "
        TRUNCATE `product_company`
        ";
        $resultEmptyTables19  = $db->sql_query($SQLEmptyTables19);

        $SQLEmptyTables20 = "
        TRUNCATE `product_group_staff`
        ";
        $resultEmptyTables20  = $db->sql_query($SQLEmptyTables20);

        $SQLEmptyTables21 = "
        TRUNCATE `product_price`
        ";
        $resultEmptyTables21  = $db->sql_query($SQLEmptyTables21);

        $SQLEmptyTables22 = "
        TRUNCATE `purchase_order`
        ";
        $resultEmptyTables22  = $db->sql_query($SQLEmptyTables22);

        $SQLEmptyTables23 = "
        TRUNCATE `quote`
        ";
        $resultEmptyTables23  = $db->sql_query($SQLEmptyTables23);

        $SQLEmptyTables24 = "
        TRUNCATE `quote_product`
        ";
        $resultEmptyTables24  = $db->sql_query($SQLEmptyTables24);

        $SQLEmptyTables25 = "
        TRUNCATE `receipt`
        ";
        $resultEmptyTables25  = $db->sql_query($SQLEmptyTables25);

        $SQLEmptyTables26 = "
        TRUNCATE `staff_attendance`
        ";
        $resultEmptyTables26  = $db->sql_query($SQLEmptyTables26);

        $SQLEmptyTables27 = "
        TRUNCATE `sub_category`
        ";
        $resultEmptyTables27  = $db->sql_query($SQLEmptyTables27);

        $SQLEmptyTables28 = "
        TRUNCATE `supplier`
        ";
        $resultEmptyTables28  = $db->sql_query($SQLEmptyTables28);

        $SQLEmptyTables29 = "
        TRUNCATE `supplier_order`
        ";
        $resultEmptyTables29  = $db->sql_query($SQLEmptyTables29);

        $SQLEmptyTables30 = "
        TRUNCATE `supplier_order_history`
        ";
        $resultEmptyTables30  = $db->sql_query($SQLEmptyTables30);

        $SQLEmptyTables31 = "
        TRUNCATE `sql_log`
        ";
        $resultEmptyTables31  = $db->sql_query($SQLEmptyTables31);
    }

    /**
     *
     */
    function getSQLLogFormDeveloper(){
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $cpUtil = Zend_Registry::get('cpUtil');
        $formObj = Zend_Registry::get('formObj');
        $formAction = "index.php?plugin=common_login&_spAction=SQLLogFormDeveloperSubmit&showHTML=0";

        $text = "
        <form id='SqlLogStoreForm' class='SqlLogStoreForm yform columnar' method='post' action='{$formAction}'>
            {$formObj->getTARow('SQL Log', 'sql_log', '')}
        </form>
        ";

        return $text;
    }


    /**
     *
     */
    function getSQLLogFormDeveloperValidate() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $validate->resetErrorArray();
        $validate->validateData('sql_log', 'Please Enter Sql Log To Submit');

        if (count($validate->errorArray) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function getSQLLogFormDeveloperSubmit() {
        $validate = Zend_Registry::get('validate');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        $db = Zend_Registry::get('db');

        if (!$this->getSQLLogFormDeveloperValidate()){
            return $validate->getErrorMessageXML();
        }

        $sql_log = $fn->getPostParam('sql_log');

        $url = 'http://cubobillpro.usoftdev.com/admin/Sync/SqlLogInsertOnTestServer.php';

        $fields = array(
            'sql_log' => $sql_log,
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $resultGetLatestLog = curl_exec($ch);
        $data = json_decode($resultGetLatestLog, true);

        //close connection
        curl_close($ch);

        /*$sql_log = explode(";", $sql_log);

        $i = 0 ;
        foreach($sql_log as $key) {
            
            if($key != ""){
                $fa = array();
                $fa['sqllog'] = $key;
                $fa['status'] = 'No';
                
                $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'sql_log');
                $resultInsert = $db->sql_query($SQLInsert);
            }

            $i++;
        }*/

        return $validate->getSuccessMessageXML();
    }

    /**
     *
     */
    function getSQLLogLatest() {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $url = 'http://cubobillpro.usoftdev.com/admin/Sync/sqlLogSync.php';

        $sqlLog = "
        SELECT MAX(sql_log_id) AS sql_log_id
        FROM sql_log
        WHERE status = 'Yes'
        ";
        $resultLog  = $db->sql_query($sqlLog);
        $rowLog     = $db->sql_fetchrow($resultLog);

        $sqlLogLastId = $rowLog['sql_log_id'];

        $fields = array(
            'sql_log_id' => $sqlLogLastId,
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        //execute post
        $resultGetLatestLog = curl_exec($ch);
        $data = json_decode($resultGetLatestLog, true);

        //close connection
        curl_close($ch);

        if(!empty($data)) {
            for($i=0;$i<sizeof($data['data']);$i++){
                $sql_log_id = $data['data'][$i]['sql_log_id'];
                $sql_log    = $data['data'][$i]['sqllog'];
                $sql_log    = addslashes($sql_log); 

                $fa = array();
                $fa['sql_log_id'] = $sql_log_id;
                $fa['sqllog']     = $sql_log;
                $fa['status']     = 'No';
                
                $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'sql_log');
                $resultInsert = $db->sql_query($SQLInsert);
            }

            $appendSql = "";
            if($sqlLogLastId != ""){
                $appendSql = "WHERE sql_log_id > {$sqlLogLastId}";
            }

            $sqlCheckRun = "
            SELECT sqllog
            FROM sql_log
            {$appendSql}
            ";
            $resultsqlCheckRun  = $db->sql_query($sqlCheckRun);
            while($rowsqlCheckRun     = $db->sql_fetchrow($resultsqlCheckRun)){

                $sqlFromSqlLog = stripslashes($rowsqlCheckRun['sqllog']);
                
                $sqlUpdateLog     = "{$sqlFromSqlLog}";
                $resultUpdateLog  = $db->sql_query($sqlUpdateLog);

            }

            $sqlChangeStatus = "
            UPDATE sql_log SET status = 'Yes'
            {$appendSql}
            ";
            $resultChangeStatus  = $db->sql_query($sqlChangeStatus);
        }
    }

    /**
     *
     */
    function getSQLLogLatestFromFile() {
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dbUtil = Zend_Registry::get('dbUtil');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln = Zend_Registry::get('ln');
        $tv = Zend_Registry::get('tv');
        $fn = Zend_Registry::get('fn');

        $sqlLog = "
        SELECT MAX(sql_log_id) AS sql_log_id
        FROM sql_log
        WHERE status = 'Yes'
        ";
        $resultLog  = $db->sql_query($sqlLog);
        $rowLog     = $db->sql_fetchrow($resultLog);

        $sqlLogLastId = $rowLog['sql_log_id'];

        $fileSQL = "Sync/clientSqlLog.txt";
        $sqllogFile = fopen($fileSQL, "r") or exit("Unable to open file!");
        
        $lastIdCheck = $sqlLogLastId+1;
        while(!feof($sqllogFile))
        {
            $fileContainsLog = fgets($sqllogFile);
            if (strpos($fileContainsLog, "[".$lastIdCheck."]") !== false){
                $fileContainsLog = str_replace("[".$lastIdCheck."]", "", $fileContainsLog);

                $fa = array();
                $fa['sql_log_id'] = $lastIdCheck;
                $fa['sqllog']     = $fileContainsLog;
                $fa['status']     = 'No';
                
                $SQLInsert = $dbUtil->getInsertSQLStringFromArray($fa, 'sql_log');
                $resultInsert = $db->sql_query($SQLInsert);
                
                $lastIdCheck++;
            }
        }

        fclose($sqllogFile);

        $appendSql = "";
        if($sqlLogLastId != ""){
            $appendSql = "WHERE sql_log_id > {$sqlLogLastId}";
        }

        $sqlCheckRun = "
        SELECT sqllog
        FROM sql_log
        {$appendSql}
        ";
        $resultsqlCheckRun  = $db->sql_query($sqlCheckRun);
        while($rowsqlCheckRun     = $db->sql_fetchrow($resultsqlCheckRun)){

            $sqlFromSqlLog = stripslashes($rowsqlCheckRun['sqllog']);
            
            $sqlUpdateLog     = "{$sqlFromSqlLog}";
            $resultUpdateLog  = $db->sql_query($sqlUpdateLog);

        }

        $sqlChangeStatus = "
        UPDATE sql_log SET status = 'Yes'
        {$appendSql}
        ";
        $resultChangeStatus  = $db->sql_query($sqlChangeStatus);
    }   
}