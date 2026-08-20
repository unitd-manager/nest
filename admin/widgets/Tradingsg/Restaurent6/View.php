<?
class CPL_Admin_Widgets_Tradingsg_Restaurent6_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
  function getWidget() {
    $db = Zend_Registry::get('db');
    $fn = Zend_Registry::get('fn');

    $text = "
    <h2>Restaurant 6</h2>
    <div class='tableOuter scroll-pane'>
        <table class='thinlist'>
            <thead>
                <tr>
                    <h1>Attendance (25/11/2024)</h1>
                    <div>
                        <strong><h3>Present</h3></strong>
                        <div class='thinlist1'>Mohammed (10:12 AM)</div>
                        <div class='thinlist1'>Jaffar Ali (10:02 AM)</div>
                        <div class='thinlist1'>Raheem (10:14 AM)</div>
                         <div class='thinlist1'>Rassol (10:12 AM)</div>
                        <div class='thinlist1'>Omar (10:02 AM)</div>
                        <div class='thinlist1'>Disath Begam (10:14 AM)</div>
                    </div>
                </tr>
            </thead>
            <tbody>
               
            </tbody>
        </table>
    </div>
    ";
    return $text;
}

    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        
        $rows = '';
        $count = 1;
        $sold_qty = '';

        foreach($this->model->dataArray as $row){

            $rows .= "
            <tr>
                <td>{$count}</td>
                <td>{$row['item_title']}</td>
                <td class='txtRight'>{$row['sold_qty']}</td>
            </tr>
            ";

            $count++;
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}