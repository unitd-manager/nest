<?
class CPL_Admin_Widgets_Tradingsg_ProductDelivery_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Product Delivery</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Date</th>
                        <th>product Name</th>
                        <th>Customer Name</th>
                        <th>Mobile Number</th>
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
        $count = 1;

        foreach($this->model->dataArray as $row){

            $delivery_date = $fn->getCPDate($row['delivery_date'], 'd-m-Y');


            $rows .= "
            <tr>
                <td>{$count}</td>
                <td>{$delivery_date}</td>
                <td>{$row['item_title']}</td>
                <td>{$row['company_name']}</td>
                <td>{$row['mobile']}</td>
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