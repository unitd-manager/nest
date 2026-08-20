<?
class CPL_Admin_Widgets_Tradingsg_Restaurent5_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
  function getWidget() {
    $db = Zend_Registry::get('db');
    $fn = Zend_Registry::get('fn');

    $text = "
    <h2>Restaurant 5</h2>
    <div class='tableOuter scroll-pane'>
        <table class='thinlist'>
            <thead>
                <tr>
                    <h1>Attendance (21/11/2024)</h1>
                    <div>
                        <strong><h3>Present</h3></strong>
                         <div class='thinlist1'>Riyaz (10:12 AM)</div>
                        <div class='thinlist1'>Ahmed (10:02 AM)</div>
                        <div class='thinlist1'>Sajra (10:14 AM)</div>
                        <div class='thinlist1'>Zareen (10:12 AM)</div>
                        <div class='thinlist1'>Ismail (10:02 AM)</div>
                        <div class='thinlist1'>Jauwad (10:14 AM)</div>
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