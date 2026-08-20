<?
class CPL_Admin_Widgets_Project_SalesByYear_Model extends CP_Admin_Widgets_Project_SalesByYear_Model
{
    /**
     *
     */
    function getSQL(){
        $SQL = "
        SELECT *
        FROM product
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;

    }

    /**
     *
     */
    function getDataArray() {
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        return;

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'Project_SalesByYear');

        $arr = array();
        foreach ($dataArray as $row){
            $tmpArr = &$arr[];
            $tmpArr['value1'] = $row['value1'];
            $tmpArr['value2'] = $row['value2'];
        }

        $this->dataArray = $arr;
        return $this->dataArray;
    }

    //==================================================================//
    function getFldSfx() {
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        if ($cpCfg['m.project.hasMultiCurrency'] == 1){
            return '_base';
        }
    }
}