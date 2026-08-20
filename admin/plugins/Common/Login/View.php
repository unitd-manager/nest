<?
class CPL_Admin_Plugins_Common_Login_View extends CP_Admin_Plugins_Common_Login_View
{

    /**
     *
     */
    function getLoginForm(){
        $hook = getCPPluginHook('common_login', 'loginForm', $this);
        if($hook['status']){
            return $hook['html'];
        }

        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');
        $cpUtil = Zend_Registry::get('cpUtil');
        $formObj = Zend_Registry::get('formObj');
        $formObj->mode = 'edit';

        if (!isset($_SESSION['returnUrlAfterLogin'])){
            $_SESSION['returnUrlAfterLogin'] =  @$_SERVER['HTTP_REFERER'];
        }

        $formAction = 'index.php?plugin=common_login&_spAction=loginSubmit&showHTML=0';
        $expPass['password'] = 1;
        $expPass['disableAutoComplete'] = $cpCfg['p.common.login.disableAutoCompleteTextFld'];

        $expEmail = array('disableAutoComplete' => $cpCfg['p.common.login.disableAutoCompleteTextFld']);

        $saveLoginText = '';
        if ($cpCfg['cp.adminHasRememberLogin']) {
            $saveLoginText = "
            <div class='type-check'>
                <input type='checkbox' id='fld_save_login' class='checkBox' name='saveLogin' value='1' />
                <label for='fld_save_login'>Save Login</label>
            </div>
            ";
        }

        $text = "
        <div id='loginOuter'>
            <div class='subcolumns'>
                <div class='c38l'>
                    <div class='subcl pr0'>
                        {$ln->gd('loginIntro')}
                    </div>
                </div>
                <div class='c62r'>
                    <div class='subcr'>
                        <form name='loginForm' id='loginForm' class='yform login cpJqForm' method='post' action='{$formAction}'>
                            <fieldset>
                                <h1>Login</h1>
                                <div id='errorDisplayBox'></div>
                                {$formObj->getTextBoxRow('Email', 'email', '', $expEmail)}
                                {$formObj->getTextBoxRow('Password', 'pass_word', '', $expPass)}
                                {$saveLoginText}
                                <input type='submit' name='submit' class='button' value='Login' />
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $text;
    }

    /**
     *
     */
    function getChooseUsergroupForm($staffRow){
        $hook = getCPPluginHook('common_login', 'chooseUsergroupForm', $this, $staffRow);
        if($hook['status']){
            return $hook['html'];
        }

        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');
        $cpUtil = Zend_Registry::get('cpUtil');
        $formObj = Zend_Registry::get('formObj');
        $formObj->mode = 'edit';

        $formAction = 'index.php?plugin=common_login&_spAction=chooseUsergroupSubmit&showHTML=0';
        $saveLogin = $fn->getPostParam('saveLogin', '', true);

        $sqlGroup = "
        SELECT DISTINCT a.user_group_id
              ,b.title
        FROM mod_acc_shop_user_group a
        LEFT JOIN user_group b ON (a.user_group_id = b.user_group_id)
        WHERE a.staff_id = '{$staffRow['staff_id']}'
        ORDER BY b.title
        ";

        $text = "
        <form name='chooseUsergroupForm' id='chooseUsergroupForm' class='yform login cpJqForm' method='post' action='{$formAction}'>
            <fieldset>
                <h1>Choose Usergroup</h1>
                {$formObj->getDDRowBySQL('Usergroup', 'user_group_id', $sqlGroup)}
                <input type='submit' name='submit' class='button' value='Submit' />
                <input type='hidden' name='staff_id' value='{$staffRow['staff_id']}' />
                <input type='hidden' name='saveLogin' value='{$saveLogin}' />
            </fieldset>
        </form>
        ";

        return $text;
    }

    /**
     *
     */
    function getSyncFilesFromServerToLocal(){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');
        $cpUtil = Zend_Registry::get('cpUtil');

        $docRoot = $_SERVER['DOCUMENT_ROOT'];

        require_once($docRoot."/admin/Sync/Client.php");

        $SECRET = $cpCfg['cp.serverSyncSecretKey']; //this must match the secret key on the server
        //const PATH = 'C:/wamp64/www/cubobillpro/httpdocs/admin/images'; //target for files synced from server
        $PATH = $docRoot."/admin";
        
        $client = new Client($SECRET, $PATH);
        $client->run($cpCfg['cp.syncFolderServerPath']); //connect to server and start sync
    }

}
