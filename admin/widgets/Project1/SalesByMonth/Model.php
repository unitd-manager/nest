<?
class CPL_Admin_Widgets_Project_SalesByMonth_Model extends CP_Admin_Widgets_Project_SalesByMonth_Model
{
    /**
     *
     */
    function getSQL(){
        $SQL = "
        SELECT * FROM product
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
     * @param <type> $SQL
     * @return <type>
     */
    function getDataArray() {
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');
        
        return;

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'project_salesByMonth');

        $arr = array();
        foreach ($dataArray as $row){
            $tmpArr = &$arr[];
            $tmpArr['yearMonth'] = $row['yearMonth'];
            $tmpArr['projectValue'] = $row['project_value_ref'];
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