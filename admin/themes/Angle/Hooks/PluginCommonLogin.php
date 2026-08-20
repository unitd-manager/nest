<?
class CPL_Admin_Themes_Angle_Hooks_PluginCommonLogin extends CP_Admin_Themes_Angle_Hooks_PluginCommonLogin
{
    /**
     *
     */
    function getLoginForm(){
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');
        $db = Zend_Registry::get('db');
        $cpUtil = Zend_Registry::get('cpUtil');
        $formObj = Zend_Registry::get('formObj');

        if($cpCfg['cp.activationStatus'] == 'Activated'){

            if (!isset($_SESSION['returnUrlAfterLogin'])){
                $_SESSION['returnUrlAfterLogin'] =  @$_SERVER['HTTP_REFERER'];
            }

            $formAction = 'index.php?plugin=common_login&_spAction=loginSubmit&showHTML=0';
            $expPass['password'] = 1;
            $expPass['disableAutoComplete'] = $cpCfg['p.common.login.disableAutoCompleteTextFld'];

            $expEmail = array('disableAutoComplete' => $cpCfg['p.common.login.disableAutoCompleteTextFld']);

            $url = 'index.php?plugin=member_forgotPassword&_spAction=view&showHTML=0';
            $forgotText = "
            <div class='forgotPasswordLink'>
                <a href='javascript:void(0)' link='{$url}' class='jqui-dialog-form' formId='forgotPasswordForm'
                    w='400' h='300' title='{$ln->gd('Recover My Password')}'>
                    {$ln->gd('Forgot Password')}
                </a>
            </div>
            ";

            $SQLActivation = "
            SELECT status
                   ,expiry_date
            FROM lead_history
            WHERE status = 'Trial'
            ";
            $resultActivation  = $db->sql_query($SQLActivation);
            $numRowsActivation = $db->sql_numrows($resultActivation);
            $rowActivation     = $db->sql_fetchrow($resultActivation);

            $activation_expiry_status = "";
            if($numRowsActivation > 0){
                $currentDate  = date("Y-m-d");
                $expiry_date_activation =  $rowActivation['expiry_date'];

                $formActionProductKeyLink = "index.php?plugin=common_login&_spAction=UpdateTrialToFullVersion&showHTML=0";
                if($expiry_date_activation == $currentDate){
                    $activation_expiry_status = "
                    <div class='btn btn-danger btn-lg activateToFullversionBtn'>
                        <a href='{$formActionProductKeyLink}' class='activateToFullversion'><span class='fa-key'></span><b>Click Here To Activate</b></a>
                    </div>
                    ";
                }else{
                    $activation_expiry_status = "
                    <div class='btn btn-success btn-lg activateToFullversionBtn'>
                        <a href='{$formActionProductKeyLink}' class='activateToFullversion'><span class='fa-key'></span><b>Click Here To Activate</b></a>
                    </div>
                    ";
                }
            }

            if($cpCfg['cp.demoVersionStatus'] == 1){
                $activation_expiry_status = "
                <div class='btn btn-success btn-lg activateToFullversionBtn'>
                    <a class='registeredFromDemoVersion'>
                        <span class='fa-registerUser'></span>
                        <b>Click Here For Customer Registration</b>
                    </a>
                </div>
                ";
            }

            if(CP_HOST == "cubobillpro.usoftdev.com"){
                $activation_expiry_status = "";
            }

            $paypal = '';
            if ($cpCfg['p.common.login.adminHasPaypalSubscription']) {
                //one month subscribe code
                /*
                $paypal = "
                <div class='paypalLoginForm float_right'>
                <form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_top'>
                <input type='hidden' name='cmd' value='_s-xclick'>
                <input type='hidden' name='hosted_button_id' value='27E34SR4ZD6ME'>
                <input type='image' src='https://www.paypalobjects.com/en_GB/SG/i/btn/btn_subscribeCC_LG.gif' border='0' name='submit' alt='PayPal – The safer, easier way to pay online.'>
                <img alt='' border='0' src='https://www.paypalobjects.com/en_GB/i/scr/pixel.gif' width='1' height='1'>
                </form>
                </div>
                ";
                */
                //one day subscribe code
                $paypal="
                <div class='paypalLoginForm float_right'>
                    <form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_top'>
                    <input type='hidden' name='cmd' value='_s-xclick'>
                    <input type='hidden' name='hosted_button_id' value='AKGUDDLMAQXCW'>
                    <input type='image' src='https://www.paypalobjects.com/en_GB/SG/i/btn/btn_subscribeCC_LG.gif' border='0' name='submit' alt='PayPal – The safer, easier way to pay online.'>
                    <img alt='' border='0' src='https://www.paypalobjects.com/en_GB/i/scr/pixel.gif' width='1' height='1'>
                    </form>
                </div>
                ";
            }

            //{$paypal}
            $text = "
            <div class='btnRegisterDemoActivate'>
                {$activation_expiry_status}
            </div>
            <div id='loginOuter'>
                <form name='loginForm' id='loginForm' class='yform columnar login cpJqForm' method='post' action='{$formAction}'>
                    <fieldset>
                        <h1>Login</h1>
                        <div id='errorDisplayBox'></div>
                        {$formObj->getTextBoxRow('Email', 'email', '', $expEmail)}
                        {$formObj->getTextBoxRow('Password', 'pass_word', '', $expPass)}
                        <input type='submit' name='submit' class='btn-md btn btn-info' value='Login' />
                        <div class='flaotbox'>
                            <div class='float_left'>
                                <div class='type-check mt10'>
                                    <input type='checkbox' id='fld_save_login' class='checkBox' name='saveLogin' value='1' checked='checked' />
                                    <label for='fld_save_login'>Save Login</label>
                                </div>
                            </div>
                            <div class='float_left'>
                                {$forgotText}
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            ";
        }
        else{
            $text = $this->getInstallationForm();
        }

        return $text;
    }

    /**
     *
     */
    function getInstallationForm(){
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');
        $cpUtil = Zend_Registry::get('cpUtil');
        $formObj = Zend_Registry::get('formObj');

        $formAction = 'index.php?plugin=common_login&_spAction=installationFormSubmit&showHTML=0';
        $expLimit = array('charsLimit' => 19);

        $text = "
        <div class='installationFormMain mt10'>
            <div class='panel panel-info'>
                <div class='panel-heading'>
                  <h1 class='panel-title'>Welcome to POS System!</h1><br/>
                  <h3 class='panel-title'>Please Fill The Following Information for Initial Setup <span class='fa-arrow-right'></span></h3>
                  <div class='btn btn-primary rollBackToDemoVerBtn'>
                        <a class='rollBackToDemoVersion'>
                            <span class='fa-share-square-o'></span>
                            <b>Click Here For Roll Back To Demo Version</b>
                        </a>
                    </div>
                </div>
                <div class='panel-body'>
                    <div class='col-md-12 row'>
                        <div class='col-md-6'>
                            <img src='/admin/images/pos_image_installation_banner.png' class='img-responsive'/>
                        </div>
                        <div class='col-md-6'>
                            <form id='portalForm_InstallationForm' class='yform columnar InstallationForm' method='post' action='{$formAction}'>
                                {$formObj->getTBRow('Agent Code', 'agent_code', '')}
                                {$formObj->getTBRow('Company Name', 'company_name', '')}
                                {$formObj->getTBRow('D.No, Address Street', 'address1', '')}
                                {$formObj->getTBRow('Address Area', 'address2', '')}
                                {$formObj->getTBRow('City - Pincode', 'address3', '')}
                                {$formObj->getTBRow('GST Number', 'gst_no', '')}
                                {$formObj->getTBRow('Email', 'admin_email', '')}
                                {$formObj->getTBRow('Mobile Number', 'mobile_no', '')}
                                {$formObj->getTBRow('Landline Number', 'landline_no', '')}
                                {$formObj->getTBRow('Product Key', 'product_key', '', $expLimit)}
                                <input class='btn btn-info installationFormSubmitButton' type='submit' value='Save & Acivate' name='InstallationForm' />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

}