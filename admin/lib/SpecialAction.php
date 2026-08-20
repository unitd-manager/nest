<?
class CPL_Admin_Lib_SpecialAction extends CP_Admin_Lib_SpecialAction {

    //==================================================================//
    function getPublishQuoteRecordByID() {
        $db = Zend_Registry::get('db');
        $tv = Zend_Registry::get('tv');
        $modulesArr = Zend_Registry::get('modulesArr');
        $cpCfg = Zend_Registry::get('cpCfg');
        $cpUtil = Zend_Registry::get('cpUtil');
        $fn = Zend_Registry::get('fn');
        $listObj = Zend_Registry::get('listObj');

        $record_id    = $fn->getPostParam('record_id');
        $module       = $fn->getPostParam('room');
        $currentValue = $fn->getPostParam('currentValue');
        $uploadTo     = $fn->getPostParam('uploadTo', 'live');
        $reUpload     = $fn->getPostParam('reUpload', 0);

        if ($reUpload == 1) {
            $newValue  = 1;
        } else {
            $newValue  = ($currentValue == 0) ? 1 : 0;
        }

        /* if newValue = 0 it means the record has to be un-published
         if newValue = 1 it means the record has to be published
        */


        $tableName    = $modulesArr[$module]['tableName'];
        $keyFieldName = $modulesArr[$module]['keyField'];

        if (!is_numeric ($record_id)) {
            print "error:not a number";
            return;
        }

        //-----------------------------------------------------//
        $updateSQL = "
        UPDATE {$tableName}
        SET general_quotation = {$newValue}
        WHERE {$keyFieldName} = {$record_id}
        ";
        $result = $db->sql_query($updateSQL);

        $text = $listObj->getListQuotePublishedImageIcon($module, $record_id, $newValue);

        return $text;
    }
}