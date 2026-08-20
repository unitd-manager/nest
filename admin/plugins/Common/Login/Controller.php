<?
class CPL_Admin_Plugins_Common_Login_Controller extends CP_Admin_Plugins_Common_Login_Controller
{
    /**
     *
     */
    function getLoginForm(){
        return $this->view->getLoginForm();
    }

    /**
     *
     */
    function getLoginSubmit(){
        return $this->model->getLoginSubmit();
    }

    /**
     *
     */
    function getChooseUsergroupSubmit(){
        return $this->model->getChooseUsergroupSubmit();
    }

    /**
     *
     */
    function loginWithCookie(){
        return $this->model->loginWithCookie();
    }

    /**
     *
     */
    function getAutoLoginRandomID(){
        return $this->model->getAutoLoginRandomID();
    }

    /**
     *
     */
    function getAutoLoginUserByRandomID(){
        return $this->model->getAutoLoginUserByRandomID();
    }
    
    /**
     *
     */
    function getLogout() {
        return $this->model->getLogout();
    }

    /**
     *
     */
    function getInstallationFormSubmit() {
        return $this->model->getInstallationFormSubmit();
    }

    /**
     *
     */
    function getPrintLoginDetails() {
        return $this->model->getPrintLoginDetails();
    }

    /**
     *
     */
    function getUnpublishLoginForExpired() {
        return $this->model->getUnpublishLoginForExpired();
    }

    /**
     *
     */
    function getUpdateTrialToFullVersion() {
        return $this->model->getUpdateTrialToFullVersion();
    }

    /**
     *
     */
    function getUpdateTrialToFullVersionSubmit() {
        return $this->model->getUpdateTrialToFullVersionSubmit();
    }

    /**
     *
     */
    function getSyncFilesFromServerToLocal() {
        return $this->view->getSyncFilesFromServerToLocal();
    }

    /**
     *
     */
    function getChangeToCustomerVersion() {
        return $this->model->getChangeToCustomerVersion();
    }

    /**
     *
     */
    function getChangeToDemoVersion() {
        return $this->model->getChangeToDemoVersion();
    }

    /**
     *
     */
    function getResetDBForInstallation() {
        return $this->model->getResetDBForInstallation();
    }

    /**
     *
     */
    function getSQLLogFormDeveloper() {
        return $this->model->getSQLLogFormDeveloper();
    }

    /**
     *
     */
    function getSQLLogFormDeveloperSubmit() {
        return $this->model->getSQLLogFormDeveloperSubmit();
    }

    /**
     *
     */
    function getSQLLogLatest() {
        return $this->model->getSQLLogLatest();
    }

    /**
     *
     */
    function getSQLLogLatestFromFile() {
        return $this->model->getSQLLogLatestFromFile();
    }

}