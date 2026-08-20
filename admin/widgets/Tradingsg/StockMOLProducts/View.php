<?
class CPL_Admin_Widgets_Tradingsg_StockMOLProducts_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Stock MOL Products</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>product Name</th>
                        <th>Supplier</th>
                        <th>stock</th>
                    </tr>
                </thead>
                <tbody>
                    {$this->getRowsHTML()}
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


        foreach($this->model->dataArray as $row){


            $rows .= "
            <tr>
                <td>{$row['product_name']}</td>
                <td>{$row['company_name']}</td>
                <td class='txtRight'>{$row['actual_stock']}</td>
            </tr>
            ";

        }

        $text = "
        {$rows}
        ";

        return $text;
    }
}