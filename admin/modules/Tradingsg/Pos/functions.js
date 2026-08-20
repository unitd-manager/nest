Util.createCPObject('cpm.tradingsg.pos');

cpm.tradingsg.pos = {
    init: function(){
        $(".addProduct input[name='product_title']")
        .livequery(cpm.tradingsg.pos.posProductTitle);

        $("input[name='customer_name']")
        .livequery(cpm.tradingsg.pos.posCustomerName);

        /*$(".checkProductPrice input[name='product_title']")
        .livequery(cpm.tradingsg.pos.checkProductPrice);*/

        /*Focus By Click Enter in POS starts*/

        $(".addProduct input[name='product_title']").live("keydown", function (e) {
            var keyCode = e.keyCode ? e.keyCode : e.which;
            var titleVal = $('#fld_product_title').val();
            if (keyCode == 13) {
                if(titleVal ==''){
                     //$("#orderItems>tr").parents('tr').prev('tr').find('input[name=qty]:first').focus();
                     $("#orderItems input[name='qty']:first").focus();
                     $("#orderItems input[name='qty']:first").select();
                }
            }
        });

        $("#orderItems input[name='qty']").live("keydown", function (e) {
            var keyCode = e.keyCode ? e.keyCode : e.which;
            var order_item_id = $(this).attr('order_item_id');
            if (keyCode == 13) {
                //$("#orderItems ."+order_item_id+" select[name='discount_type']").focus();
                $(".addProduct input[name='product_title']").focus();
            }
        });

        $("#orderItems select[name='discount_type']").live("keydown", function (e) {
            var keyCode = e.keyCode ? e.keyCode : e.which;
            var order_item_id = $(this).closest('td').attr('order_item_id');
            if (keyCode == 13) {
               $("#orderItems ."+order_item_id+" input[name='discount_percentage']").focus();
            }
        });

        $("#orderItems input[name='discount_percentage']").live("keydown", function (e) {
            var keyCode = e.keyCode ? e.keyCode : e.which;
            var order_item_id = $(this).attr('order_item_id');
            if (keyCode == 13) {
                $(".addProduct input[name='product_title']").focus();
            }
        });

        cpm.tradingsg.pos.colorChange();

        /*Focus By Click Enter in POS starts*/

        $("#orderItems input[name='qty']").livequery('change', function(){
            var qty = $(this).val();
            var order_item_id = $(this).attr('order_item_id');
            var stock =parseInt($(this).attr('stock'), 10);
            //Util.showProgressInd();
            /*if(stock < qty){
                Util.alert('The qty should be less than the stock qty');
                    cpm.tradingsg.pos.reloadOrderItems();
            } else {*/
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_pos&_spAction=updateQtyOrderItem&showHTML=0';
                $.get(url, {qty: qty, order_item_id: order_item_id}, function(html){

                    /*var html='Processing...';
                    var n = noty({
                        text: html,
                        type: 'confirm',
                        dismissQueue: true,
                        layout: 'topCenter',
                        theme: 'defaultTheme',
                        timeout: 1000,
                    });*/

                    cpm.tradingsg.pos.reloadOrderItems('qty', order_item_id);
                    //Util.hideProgressInd();
                });
            //}
        });

        $("#orderItems input[name='weight']").livequery('change', function(){
            var weight = $(this).val();
            var order_item_id = $(this).attr('order_item_id');

            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=updateWeightOrderItem&showHTML=0';
            $.get(url, {weight: weight, order_item_id: order_item_id}, function(html){

                cpm.tradingsg.pos.reloadOrderItems();
            });
        });

        $("#orderItems input[name='discount']").livequery('change', function(){
            var discount = $(this).val();
            var order_id = $(this).attr('order_id');
            var url = 'index.php?module=tradingsg_pos&_spAction=updateDiscountOrder&showHTML=0';
            $.get(url, {discount: discount, order_id: order_id}, function(html){
                cpm.tradingsg.pos.reloadOrderItems('add_product');
            });
        });

        $("#orderItems input[name='amount_given']").livequery('change', function(){
            var amount_given = $(this).val();
            var netTotal = $(this).attr('total');

            if(netTotal == 0){
                $(this).val(0);
                alert('Please Select Some Product(s) To Pay');
            }
            else{
                if(parseFloat(amount_given) < parseFloat(netTotal)){
                    var url = 'index.php?module=tradingsg_pos&_spAction=updateBalance&showHTML=0';
                    $.get(url, {amount_given: amount_given, netTotal: netTotal}, function(html){
                        $('.balanceRow').show();
                        $('.balance').html(html);
                    });

                    //alert('Please Enter Amount Greater Or Equal To Net Total!');
                    $("#orderItems input[name='amount_given']").focus();
                }
                else{
                    var url = 'index.php?module=tradingsg_pos&_spAction=updateBalance&showHTML=0';
                    $.get(url, {amount_given: amount_given, netTotal: netTotal}, function(html){

                        $('.balanceRow').show();
                        $('.balance').html(html);
                    });
                }
            }
        });

        $('#newOrderNormal').livequery('click', function (){
            var msg = "Do you like to create a New Order?";
            if (confirm(msg)){
                var url = 'index.php?module=tradingsg_pos&_spAction=createNewOrder&showHTML=0&checkLastOrder=1';
                $.get(url, function(html){
                    window.location.reload(true);
                });
            }
        });

        $('#newOrder').livequery('click', function (e){
            var msg = "Do you like to create a New Order?";
            if (confirm(msg)){
                var title = "Sale by name";
                e.preventDefault();
                var expObj = {
                    validate: true
                   ,callbackOnSuccess: function(){
                        Util.closeAllDialogs();
                        var url = 'index.php?module=tradingsg_pos&_spAction=createNewOrder&showHTML=0&checkLastOrder=1';
                        $.get(url, function(html){
                            window.location.reload(true);
                        });
                    }
                }
                Util.openFormInDialog.call(this, 'portalSaleNameForm', title, 490, 250, expObj);
            }
        });

        $('.pendingOrderID').livequery('click', function (){
            var parent = $(this).closest('tr');
            var order_id = $(parent).attr('order_id');
            //alert(order_id);
            var msg = "Do you like to work on this Order?";
            if (confirm(msg)){
                var url = 'index.php?module=tradingsg_pos&_spAction=insertOldOrder&showHTML=0';
                $.get(url, {order_id:order_id}, function(html){
                    window.location.reload(true);
                });
            }
        });

        $("#clientHistory").livequery('click', function (e){
            Util.showProgressInd();
            var order_item_id = $(this).attr('order_item_id');
            var url = "index.php?module=tradingsg_pos&_spAction=showClientHistory&order_item_id=" + order_item_id + '&showHTML=0';
            var exp = {
                url: url
            };
            Util.openDialogForLink('Client History',  600, 250, 0, exp);
        });

                // CHECK PENDING ORDER //
        $("#checkPendingOrder").livequery('click', function (e){
            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=checkPendingOrderDetails&showHTML=0';
            var exp = {
                url: url
            };
            Util.openDialogForLink('Check Pending Order',  900, 400, 0, exp);
        });

        $("#changeStatusPending").livequery('click', function (e){
            e.preventDefault();

            var msg = "Please confirm, if you would like to change the Order to Pending, this would close the Order";
            if (confirm(msg)){
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_pos&_spAction=orderStatusToPending&showHTML=0';
                $.get(url, function(html){
                    //window.location.reload(true);
                    cpm.tradingsg.pos.createneworder();
                });
            }

            Util.openDialogForLink('Change Status To Pending',  900, 400, 0, exp);
        });


        $('#cancelOrder').livequery('click', function (){
            var msg = "Do you like to Close the Order?";
            if (confirm(msg)){
                var url = 'index.php?module=tradingsg_pos&_spAction=cancelOrder&showHTML=0';
                $.get(url, function(html){
                    window.location.reload(true);
                });
            }
        });

        /* Updating of discount group in quote_product table by product group */
        $('#applyDiscount').livequery('click', function (e){
            var title = "Update Discount";
            e.preventDefault();
            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    //window.location.reload(true);
                    cpm.tradingsg.pos.reloadOrderItems();
                }
            }
            Util.openFormInDialog.call(this, 'portalPOSApplyDiscountForm', title, 300, 260, expObj);
        });

        /* Add Client in POS */
        $('#addClient').livequery('click', function (e){
            var title = "Add Customer";
            e.preventDefault();
            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    window.location.reload(true);
                }
            }
            Util.openFormInDialog.call(this, 'portalForm', title, 500, 500, expObj);
        });

        $('#removeClient').livequery('click', function (){
            var url = 'index.php?module=tradingsg_pos&_spAction=removeClient&showHTML=0';
            $.get(url, function(html){
                $("#customerDetailsDisplay").html('');
                window.location.reload(true);
            });
        });

        $('#closeOrder').livequery('click', function (){
            cpm.tradingsg.pos.closeOrder();
        });

        $('select[name=mode_of_payment]').livequery('change', function (e){
            var mode_of_payment = $(this).val();
            var urlModeOfPayment = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=modeOfPaymentUpdate&showHTML=0";
            
            $.get(urlModeOfPayment, {mode_of_payment:mode_of_payment}, function(html){
                if(mode_of_payment == "Credit Card"){
                    var url = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=creditCard&showHTML=0";
                    var title = "Credit Card Details";
                    e.preventDefault();
                    var expObj = {
                        url: url
                       ,validate: true
                       ,callbackOnSuccess: function(){
                            Util.closeAllDialogs();
                        }
                    }
                    Util.openFormInDialog.call(this, 'portalForm', title, 490, 250, expObj);
                }
            });
        });


        
        $('select[id=fld_table_value]').livequery('change', function (e){
            var table_value = $(this).val();
            var urlModeOfPayment = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=tableValueUpdate&showHTML=0";
            
            $.get(urlModeOfPayment, {table_value:table_value}, function(html){
                window.location.reload(true);

            });
        });

       $('#generateBill').livequery('click', function (){
            var netTotal =  $('#fld_netTotal_amount').html();
            //alert(netTotal);
            var netamount ='Net amount to be paid';
            var Amountpaid = $('#fld_amount_given').val();
            var amount = parseFloat(Amountpaid);
            var discount =  $('#fld_totalDiscount_amount').html();
            var space = '';
            var Change = $('.balance').html();
            var subtotal =  $('#fld_subtotal_amount').val();
            var qty =  $('#fld_qty_total').val();
            var msg = 'Do you like to Print the Order?\n\nTotal bill amount before discount\t: ' + subtotal + '\n\nTotal discount\t\t\t\t\t: ' + discount +  '\n\nTotal quantity\t\t\t\t\t\t: ' + qty + '\n________________________________________'+ space + '\n\nNet amount to be paid\t\t\t: ' + netTotal + '\n________________________________________'+ space + '\n\ncash received\t\t\t\t\t: ' + amount.toFixed(2) + space + '\n\nchange\t\t\t\t\t\t\t: ' + Change + '';
            if (confirm(msg)){
                var mode_of_payment = $('#fld_mode_of_payment').val();
                var url = 'index.php?module=tradingsg_pos&_spAction=generateBill&showHTML=0';
                $.get(url, {mode_of_payment:mode_of_payment}, function(html){
                    var printUrl = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printBill&invoice_code=" + html + '&showHTML=0';
                    window.open(printUrl,'_blank');
                    //Util.showProgressInd();
                    //cpm.tradingsg.pos.closeOrder();
                    window.location.reload(true);
                });
            }
        });

       /*$('#thermalPrinterPrint').livequery('click', function (){
            var netTotal =  $('#fld_netTotal_amount').html();
            //alert(netTotal);
            var netamount ='Net amount to be paid';
            var Amountpaid = $('#fld_amount_given').val();
            var amount = parseFloat(Math.round(Amountpaid ));
            var discount =  $('#fld_totalDiscount_amount').html();
            var space = '';
            var Change = $('.balance').html();
            var subtotal =  $('#fld_subtotal_amount').val();
            var qty =  $('#fld_qty_total').val();
            var msg = 'Do you like to Print the Order?\n\nTotal bill amount before discount\t: ' + subtotal + '\n\nTotal discount\t\t\t\t\t: ' + discount +  '\n\nTotal quantity\t\t\t\t\t\t: ' + qty + '\n________________________________________'+ space + '\n\nNet amount to be paid\t\t\t: ' + netTotal + '\n________________________________________'+ space + '\n\ncash received\t\t\t\t\t: ' + amount + '.00' + space + '\n\nchange\t\t\t\t\t\t\t: ' + Change + '';
            if (confirm(msg)){
                var mode_of_payment = $('#fld_mode_of_payment').val();
                var url = 'index.php?module=tradingsg_pos&_spAction=generateBill&showHTML=0';
                $.get(url, {mode_of_payment:mode_of_payment}, function(html){
                    var printUrl = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printBillForPrinter&invoice_code=" + html + '&showHTML=0';
                    //window.open(printUrl,'_blank');
                    $.get(printUrl, function(html){
                    });
                    //Util.showProgressInd();
                    //cpm.tradingsg.pos.closeOrder();
                    window.setTimeout(function () {
                        $('#thermalPrinter').trigger('click');
                    }, 500);
                    window.setTimeout(function () {
                        var printUrl1 = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printbillconditionForPrinter&showHTML=0";
                        /*$.get(printUrl, function(html){
                        });*/
                        /*window.open(printUrl1,'_blank');
                        window.location.reload(true);
                    }, 2000);
                });
            }
        });*/

        $('#thermalPrinterPrint').livequery('click', function (){
            //cpm.tradingsg.pos.printTrigger('iFramePdf');
            var netTotal =  $('#fld_netTotal_amount').html();
            //alert(netTotal);
            var netamount ='Net amount to be paid';
            var Amountpaid = $('#fld_amount_given').val();
            var amount = parseFloat(Amountpaid);
            var discount =  $('#fld_totalDiscount_amount').html();
            var space = '';
            var Change = $('.balance').html();
            var subtotal =  $('#fld_subtotal_amount').val();
            var qty =  $('#fld_qty_total').val();
            var order_amount = $('.TotalAmountDisplayTop').html();

            if(order_amount == 0){
                Util.alert('Please Select Some Product(s) to Proceed Print!');
            }
            else{
                if(amount == 0){
                    alert("Please Enter Amount to Paid !");
                    $('#fld_amount_given').focus();
                }
                else{
                    var msg = 'Do you like to Print the Order?\n\nTotal bill amount before discount\t: ' + subtotal + '\n\nTotal discount\t\t\t\t\t: ' + discount +  '\n\nTotal quantity\t\t\t\t\t\t: ' + qty + '\n________________________________________'+ space + '\n\nNet amount to be paid\t\t\t: ' + netTotal + '\n________________________________________'+ space + '\n\ncash received\t\t\t\t\t: ' + amount.toFixed(2) + space + '\n\nchange\t\t\t\t\t\t\t: ' + Change + '';
                    if (confirm(msg)){
                        var mode_of_payment = $('#fld_mode_of_payment').val();
                        var order_date     = $('#fld_order_date').val();
                        var receipt_amount  = amount.toFixed(2);
                        var gst_selected    = $('input[name=gst_selected]').val();
                        var url = 'index.php?module=tradingsg_pos&_spAction=generateBill&showHTML=0';
                        $.get(url, {mode_of_payment:mode_of_payment, receipt_amount:receipt_amount, gst_selected:gst_selected, order_date:order_date}, function(html){
                            var printUrl = "index.php?_topRm=finance&module=tradingsg_order&_spAction=printInvoiceRecord&order_id=" + html + "&receipt_amount=" + receipt_amount + "&change=" + Change + "&showHTML=0";
                            //var printUrl = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=printInvoiceRecord&showHTML=0";
                            //$.get(printUrl, {mode_of_payment:mode_of_payment, orderNo:html, receipt_amount:receipt_amount, change:Change}, function(html){
                                //cpm.tradingsg.pos.printPage(printUrl);
                            //});
                            window.open(printUrl,'_blank');
                            var last_order_id = $('#fld_current_order_id').val();
                            cpm.tradingsg.pos.createnewordercheck(last_order_id);
                        });
                    }
                }
            }
        });

        $('.deleteItem').livequery('click', function (){
            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=deleteItem&showHTML=0';
            var order_item_id = $(this).attr('order_item_id');
            $.get(url,  {order_item_id:order_item_id}, function(html){
                cpm.tradingsg.pos.reloadOrderItems();
            });
        });

        $('select[name=discount_type]').livequery('change', function(){
            var discount_type = $(this).val();
            var parent = $(this).closest('td');
            var order_item_id = $(parent).attr('order_item_id');
            var parent2 = $(this).closest('tr');
            var discount_percentage = $('input[name=discount_percentage]', parent2).val();

            if(discount_percentage == ''){
                discount_percentage = 0;
            }

            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=updatediscountType&showHTML=0';
            $.get(url, {discount_percentage: discount_percentage, order_item_id: order_item_id, discount_type: discount_type}, function(json){
                cpm.tradingsg.pos.reloadOrderItems('discount_type',order_item_id);
            });
        });

        $("#orderItems input[name='discount_percentage']").livequery('change', function(){
            var discount_percentage = $(this).val();
            var parent              = $(this).closest('tr');
            var order_item_id       = $(this).attr('order_item_id');
            var discount_type       = $('select[name=discount_type]', parent).val();
            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=updateDiscountPercentOrderItem&showHTML=0';
            $.get(url, {discount_percentage: discount_percentage, order_item_id: order_item_id, discount_type:discount_type}, function(html){
                cpm.tradingsg.pos.reloadOrderItems('add_product');
            });
        });

        $("#orderItems input[name='pieces']").livequery('change', function(){
            var pieces = $(this).val();
            var order_item_id = $(this).attr('order_item_id');
            var url = 'index.php?module=tradingsg_pos&_spAction=updatePiecesOrderItem&showHTML=0';
            $.get(url, {pieces: pieces, order_item_id: order_item_id}, function(html){
                cpm.tradingsg.pos.reloadOrderItems('add_product');
            });
        });

        $("#orderItems input[name='unit_price']").live('change', function () {
            var unit_price    = $(this).val();
            var order_item_id = $(this).attr('order_item_id');

            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=UpdateUnitPriceOrderItem&showHTML=0';
            $.get(url, {unit_price: unit_price, order_item_id: order_item_id}, function(html){
                cpm.tradingsg.pos.reloadOrderItems('unit_price', order_item_id);
            });
        });

        $("#cancelOrderPOS").livequery('click', function (e){
            var msg = "Do you like to Cancel the Order?";
            var order_id = $(this).attr('order_id');
            if (confirm(msg)){
                var title = "Add Notes";

                e.preventDefault();
                var expObj = {
                    url:"index.php?_topRm=pos&module=tradingsg_pos&_spAction=CancelOrderNotes&order_id="+order_id+"&showHTML=0"
                   ,validate: true
                   ,callbackOnSuccess: function(){
                        Util.closeAllDialogs();
                        alert("Order Cancelled Successfully!")
                        window.location.reload(true);
                    }
                }

                Util.openFormInDialog.call(this, 'portalCancelOrderNotesForm', title, 500, 280, expObj);
            }
        });

        $('.btn-toggle').click(function() {

            Util.showProgressInd();
            $(this).find('.btn').toggleClass('active'); 

            var selectState =  $(this).find('.btn.active').html();

            if(selectState == "ON"){
                $('input[name=gst_selected]').val(selectState);
            }else{
                $('input[name=gst_selected]').val(selectState);
            }

            var urlGstStatus = "index.php?_topRm=pos&module=tradingsg_pos&_spAction=GSTStatusUpdate&showHTML=0";
            $.get(urlGstStatus, {gst_status:selectState}, function(html){
                cpm.tradingsg.pos.reloadOrderItems();
                if(selectState == "ON"){
                    $(".GstColumnHeader").removeClass('displayNone');
                    $(".GstColumnValue").removeClass('displayNone');
                }else{
                    $(".GstColumnHeader").addClass('displayNone');
                    $(".GstColumnValue").addClass('displayNone');
                }

                Util.hideProgressInd();

            });
            
            if ($(this).find('.btn-primary').size()>0) {
                $(this).find('.btn').toggleClass('btn-primary');
            }
            if ($(this).find('.btn-danger').size()>0) {
                $(this).find('.btn').toggleClass('btn-danger');
            }
            if ($(this).find('.btn-success').size()>0) {
                $(this).find('.btn').toggleClass('btn-success');
            }
            if ($(this).find('.btn-info').size()>0) {
                $(this).find('.btn').toggleClass('btn-info');
            }
            
            $(this).find('.btn').toggleClass('btn-default');
               
        });

        $('.loyaltypoint').livequery('click', function(){
            var cust_company_name = $(this).attr('cust_company_name');
            var url = 'index.php?_topRm=pos&module=tradingsg_pos&_spAction=LoyaltyUpdate&showHTML=0';
            
            $.get(url, {cust_company_name: cust_company_name}, function(html){
                Util.hideProgressInd();
            });
        });

        /* Updating of shipping charge for order */
        $('#applyShippingCharge').livequery('click', function (e){
            var title = "Update Shipping Charges";
            e.preventDefault();
            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    cpm.tradingsg.pos.reloadOrderItems();
                }
            }
            Util.openFormInDialog.call(this, 'portalPOSApplyShippingChargesForm', title, 400, 260, expObj);
        });

        /* Remove Shipping Charge For Order */
        $('.removeShippingCharge').live('click', function (e){
            msg = "Do you like to remove the shipping charge?";
            if (!confirm(msg)){
                return false;
            }
            else{
                Util.showProgressInd();
                var order_id   = $(this).attr('order_id');

                var url = 'index.php?module=tradingsg_pos&_spAction=removeShippingChargeOrder&showHTML=0';
                $.get(url, {order_id: order_id}, function(html){
                    Util.hideProgressInd();
                    cpm.tradingsg.pos.reloadOrderItems();
                });
            }
        });

        $('.orderDatePOSHeader .hasDatepicker').livequery('change', function(e){
            var dateChanged  = $('#fld_order_date').val();
            var url = 'index.php?module=tradingsg_pos&_spAction=updateOrderDate&showHTML=0';
            $.get(url, {dateChanged:dateChanged}, function(html){
            });
        });

        $("#orderItems input[name='ref_no']").livequery('change', function(){
            var ref_no = $(this).val();
            var order_item_id = $(this).attr('order_item_id');

            Util.showProgressInd();
            var url = 'index.php?module=tradingsg_pos&_spAction=UpdateRefNoOrderItem&showHTML=0';
            $.get(url, {ref_no: ref_no, order_item_id: order_item_id}, function(html){

                cpm.tradingsg.pos.reloadOrderItems('ref_no', order_item_id);
            });
        });

        /* Add Client in POS */
        $('.discountTypeHeader').livequery('click', function (e){
            var title = "Discount Type";
            e.preventDefault();
            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    Util.closeAllDialogs();
                    window.location.reload(true);
                }
            }
            Util.openFormInDialog.call(this, 'portalPOSDiscountTypeDefaultForm', title, 400, 260, expObj);
        });
    },

    printPage: function(sURL){
        var iframeId = "iframeprint";
        var oHiddFrame = document.createElement("iframe");
        oHiddFrame.onload = cpm.tradingsg.pos.setPrint;
        oHiddFrame.id = iframeId;
        oHiddFrame.style.visibility = "hidden";
        oHiddFrame.style.position = "fixed";
        oHiddFrame.style.right = "0";
        oHiddFrame.style.bottom = "0";
        oHiddFrame.src = sURL;
        document.body.appendChild(oHiddFrame);
    },

    setPrint: function() {
        this.contentWindow = $("#iframeprint");
        this.contentWindow.focus(); // Required for IE
        this.contentWindow.print();
    },

    posProductTitle: function() {
        var titleObj = this;
        //to check if any key is pressed
        /*
        $(titleObj).keypress(function(event) {
            var keycode = (event.keyCode ? event.keyCode : event.which);
            if(keycode != '') {
                alert('You pressed a key in somewhere');
            }
        });
        */
        var barcodeinput = 1;
        $(titleObj).keypress(function(){
            barcodeinput = 0;
            //alert('key press');
        });

        $(titleObj).autocomplete({
             source : 'index.php?module=tradingsg_pos&_spAction=searchProductTitle&showHTML=0'
            ,minLength : 1
            ,selectFirst: true
            ,autoFocus: true
            ,focus: function(event, ui) {
                var len = $('.ui-autocomplete > li').length;
                //alert(barcodeinput);
                // this functions should execute only if inut is from barcode
                if(len === 1){
                    //alert('bar code');
                    var selectedObj = ui.item;
                    var product_id = selectedObj.id
                    $(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                    //--------------------------------------------
                    Util.showProgressInd();
                    var url = 'index.php?module=tradingsg_pos&_spAction=updateOrderLineItems&showHTML=0';
                    $.get(url, {product_id: product_id}, function(json){
                        cpm.tradingsg.pos.reloadOrderItems();
                        $(".addProduct input[name='product_title']").val('');
                        Util.hideProgressInd();
                    });
                    $(titleObj).autocomplete( "close" );
                    //$(titleObj).autocomplete( "close" );
                }
                barcodeinput = 1;
            }
            ,select: function(event, ui) {
                barcodeinput = 1;
                var selectedObj = ui.item;
                var product_id = selectedObj.id
                //alert (product_id);
                $(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                //--------------------------------------------
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_pos&_spAction=updateOrderLineItems&showHTML=0';
                $.get(url, {product_id: product_id}, function(json){
                    cpm.tradingsg.pos.reloadOrderItems();
                    $(".addProduct input[name='product_title']").val('');
                    Util.hideProgressInd();
                });
            }
        });
    },

    //Auto select customer details
    posCustomerName: function() {
        var titleObj = this;
        $(titleObj).autocomplete({
             source : 'index.php?module=tradingsg_pos&_spAction=searchCustomerDetails&showHTML=0'
            ,minLength : 2
            ,selectFirst: true
            ,autoFocus: true
            ,select: function(event, ui) {
                var selectedObj = ui.item;
                var company_id = selectedObj.id
                //alert (company_id);
                $(this).after("<input type='hidden' name='company_id' value=" + company_id + ">");

                //--------------------------------------------
                Util.showProgressInd();
                cpm.tradingsg.pos.reloadCustomerDetails(company_id);
            }
        });
    },

    reloadCustomerDetails: function(company_id){
        var url = 'index.php?module=tradingsg_pos&_spAction=displayCustomerDetails&showHTML=0';
        $.get(url, {company_id: company_id}, function(html){
            $('#customerDetailsDisplay').html(html);
            $("input[name='customer_name']").val('');
            Util.hideProgressInd();
            window.location.reload(true);
        });
    },

    /*checkProductPrice: function() {
        var titleObj = this;
        $(titleObj).autocomplete({
             source : 'index.php?module=tradingsg_pos&_spAction=searchProductTitle&showHTML=0'
            ,minLength : 2
            ,select: function(event, ui) {
                var selectedObj = ui.item;
                var product_id = selectedObj.id
                //alert (product_id);
                $(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                //--------------------------------------------
                Util.showProgressInd();
                var url = 'index.php?module=tradingsg_pos&_spAction=productPriceDisplay&showHTML=0';
                $.get(url, {product_id: product_id}, function(html){
                    //cpm.tradingsg.pos.reloadOrderItems();
                    $(".checkProductPrice input[name='product_title']").val('');
                    $('#productDisplay').html(html);
                    Util.hideProgressInd();
                });
            }
        });
    },*/

    reloadOrderItems: function(focus_element, obj){
         var url = 'index.php?module=tradingsg_pos&_spAction=orderItems&showHTML=0';
        //var parent = $(this).closest('td');

        $.get(url,  function(html){
            $('#orderItems').html(html);
            Util.hideProgressInd();
            /*if(focus_element == 'qty'){
                //var discPriceObj = $(obj).closest('tr').find('#discount_percentage');
                //var discPriceObj = $(obj).closest('tr').find('select[name=discount_percentage]');
                //discPriceObj.focus();
                $("#orderItems input[name='discount_percentage']").focus();
            }
            else if(focus_element == 'discount_type'){
                $("#orderItems input[name='discount_percentage']").focus();
            }
            else if(focus_element == 'add_product'){
                $(".addProduct input[name='product_title']").focus();
            }*/
            cpm.tradingsg.pos.colorChange();

            //$("#orderItems input[name='qty']").focus();
           //$("#orderItems input[name='qty']").select();
            if(focus_element == 'qty'){
                //$("#orderItems ."+obj+" input[name='discount_percentage']").focus();
                $("#orderItems ."+obj+" input[name='qty']").focus();
                $("#orderItems ."+obj+" input[name='qty']").select();
            }

            else if(focus_element == 'discount_type'){
                //$("#orderItems ."+obj+" input[name='discount_percentage']").focus();
                $(".addProduct input[name='product_title']").focus();
            }

            else if(focus_element == 'add_product'){
                $(".addProduct input[name='product_title']").focus();
            }

            else if(focus_element == 'ref_no'){
                $("#orderItems ."+obj+" input[name='ref_no']").focus();
                $("#orderItems ."+obj+" input[name='ref_no']").select();
            }

            else if(focus_element == 'unit_price'){
                $("#orderItems ."+obj+" input[name='unit_price']").focus();
                $("#orderItems ."+obj+" input[name='unit_price']").select();
            }

            var netTotal = $('#fld_netTotal_amount').html();
            $(".TotalAmountDisplayTop").html(netTotal);

            var totalProducts = $('#fld_products_total').val();
            $(".NoOfProductsOnTop").html(totalProducts);

            if(totalProducts > 0){
                $("#changeStatusPending").removeAttr('disabled');
            }else{
                $("#changeStatusPending").attr('disabled', 'disabled');
            }

        });
    },

    createneworder: function(){
        var url = 'index.php?module=tradingsg_pos&_spAction=createNewOrder&showHTML=0';
        $.get(url, function(html){
            window.location.reload(true);
        });
    },

    createnewordercheck: function(last_order_id){
        var url = 'index.php?module=tradingsg_pos&_spAction=createNewOrder&showHTML=0';
        $.get(url, {last_order_id:last_order_id}, function(html){
            window.location.reload(true);
        });
    },

    closeOrder: function(){
        var url = 'index.php?module=tradingsg_pos&_spAction=closeOrder&showHTML=0';
        $.get(url, function(html){
            window.location.reload(true);
        });
    },

    colorChange: function(){
        $("#orderItems input[name='qty']").focus(function() {
            $(this).addClass("focus");
        });

        $("#orderItems input[name='qty']").blur(function() {
            $(this).removeClass("focus");
        });

        $("#orderItems select[name='discount_type']").focus(function() {
            $(this).addClass("focus");
        });

        $("#orderItems select[name='discount_type']").blur(function() {
            $(this).removeClass("focus");
        });

        $("#orderItems input[name='discount_percentage']").focus(function() {
            $(this).addClass("focus");
        });

        $("#orderItems input[name='discount_percentage']").blur(function() {
            $(this).removeClass("focus");
        });

        $(".addProduct input[name='product_title']").focus(function() {
            $(this).addClass("focus");
        });

        $(".addProduct input[name='product_title']").blur(function() {
            $(this).removeClass("focus");
        });

        $("#orderItems input[name='weight']").focus(function() {
            $(this).addClass("focus");
        });

        $("#orderItems input[name='weight']").blur(function() {
            $(this).removeClass("focus");
        });

        $("#orderItems input[name='ref_no']").focus(function() {
            $(this).addClass("focus");
        });

        $("#orderItems input[name='ref_no']").blur(function() {
            $(this).removeClass("focus");
        });

        $("#orderItems input[name='unit_price']").focus(function() {
            $(this).addClass("focus");
        });

        $("#orderItems input[name='unit_price']").blur(function() {
            $(this).removeClass("focus");
        });
    },

    printTrigger: function(elementId){
        /*var getMyFrame = document.getElementById(elementId);
        getMyFrame.focus();
        getMyFrame.contentWindow.print();*/

          /*var printFrame = document.getElementById(elementId)
          if (printFrame) {
            printFrame.contentWindow.print()
          } else {
            PDFViewerApplication.pdfDocument.getData().then(function(res) {
              var src = URL.createObjectURL(new Blob([res], { type: 'application/pdf' }))
              printFrame = document.createElement('iframe')
              printFrame.id = elementId
              printFrame.style.display = 'none'
              printFrame.src = src
              document.body.appendChild(printFrame)
              setTimeout(function () {
                printFrame.contentWindow.print()
              }, 0)
            })
          }*/

        var iframe = document.frames ? window.frames.frames[elementId] :document.getElementById(elementId);
        var ifWin = iframe.contentWindow || iframe;
        try {
            ifWin.focus();
            ifWin.print();
        }
        catch(e) {
            window.print(false);
        }

        return false;
    }

}
