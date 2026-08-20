Util.createCPObject('cpm.tradingsg.order');

cpm.tradingsg.order = {
    init: function(){
        $('.click-all-top .check-all').livequery('click', function(e){
            e.preventDefault();
            cpm.tradingsg.order.checkAllCol.call(this);
        });

        $('.click-all-top .uncheck-all').livequery('click', function(e){
            e.preventDefault();
            cpm.tradingsg.order.uncheckAllCol.call(this);
        });

         $('.click-all-topping .check-all-col').livequery('click', function(e){
            e.preventDefault();
            cpm.tradingsg.order.checkAllColling.call(this);
        });

        $('.click-all-topping .uncheck-all-col').livequery('click', function(e){
            e.preventDefault();
            cpm.tradingsg.order.uncheckAllColling.call(this);
        });

        $('.m-tradingsg_order .actionBtnsDetail #generateInvoice').livequery('click', function (e){
            var title = "Create Invoice";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Invoice created successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        window.location.reload(true);
                    });
                }
            }
            var width = Math.min($(window).width() * 0.9, 600); // 90% of the viewport width or 600px max
            var height = Math.min($(window).height() * 0.9, 500); // 90% of the viewport height or 530px max
        
            Util.openFormInDialog.call(this, 'portalForm', title, width, height, expObj);

        });

        $('.m-tradingsg_order #editInvoice').livequery('click', function (e){
            var title = "Edit Invoice";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Invoice updated successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        window.location.reload(true);
                    });
                }
            }
            var width = Math.min($(window).width() * 0.9, 600); // 90% of the viewport width or 600px max
            var height = Math.min($(window).height() * 0.9, 500); // 90% of the viewport height or 530px max
        
            Util.openFormInDialog.call(this, 'portalForm', title, width, height, expObj);
        });

        $('.m-tradingsg_order .actionBtnsDetail #generateCredit').livequery('click', function (e){
            var title = "Create Credit Note";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Credit Note created successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        window.location.reload(true);
                    });
                }
            }
            Util.openFormInDialog.call(this, 'portalCreditForm', title, 600, 500, expObj);
        });

        $('.cancelCreditNote').livequery('click', function (e){
            var invoice_status = $(this).attr('invoice_status');

            if (invoice_status != 'Paid') {
                msg = "Do you like to cancel the Credit Note?";
                if (!confirm(msg)){
                    return false;
                }
                else {
                    var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=cancelCreditNote&showHTML=0';
                    Util.showProgressInd();
                    var invoice_code = $(this).attr('invoice_code');
                    $.get(url,{invoice_code: invoice_code}, function(html){

                        /* Checking for one or more receipt for the invoice */
                        if (html == 'Cannot cancel') {
                            alert ('Cancel the related receipts and then proceed canceling the invoice');
                            Util.hideProgressInd();
                        } else {
                            alert ('Credit Note Cancelled Succesfully');
                            Util.hideProgressInd();
                            window.location.reload(true);
                        }
                    });
                }
            } else {
                msg = "Please cancel the receipt and then try canceling the Credit";
                if (!confirm(msg)){
                    return false;
                } else {
                    return false;
                }
            }
        });

         $('.click-all-debit .check-all-col-debit').livequery('click', function(e){
            e.preventDefault();
            cpm.tradingsg.order.checkAllDebit.call(this);
        });

        $('.click-all-debit .uncheck-all-col-debit').livequery('click', function(e){
            e.preventDefault();
            cpm.tradingsg.order.uncheckAllDebit.call(this);
        });

         $('.m-tradingsg_order .actionBtnsDetail #generateDebit').livequery('click', function (e){
            var title = "Create Debit Note";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Debit Note created successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        window.location.reload(true);
                    });
                }
            }
            Util.openFormInDialog.call(this, 'portalDebitForm', title, 600, 500, expObj);
        });

        $('.cancelDebitNote').livequery('click', function (e){
            var invoice_status = $(this).attr('invoice_status');

            if (invoice_status != 'Paid') {
                msg = "Do you like to cancel the Debit Note?";
                if (!confirm(msg)){
                    return false;
                }
                else {
                    var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=cancelDebitNote&showHTML=0';
                    Util.showProgressInd();
                    var invoice_code = $(this).attr('invoice_code');
                    $.get(url,{invoice_code: invoice_code}, function(html){

                        /* Checking for one or more receipt for the invoice */
                        if (html == 'Cannot cancel') {
                            alert ('Cancel the related receipts and then proceed canceling the invoice');
                            Util.hideProgressInd();
                        } else {
                            alert ('Debit Note Cancelled Succesfully');
                            Util.hideProgressInd();
                            window.location.reload(true);
                        }
                    });
                }
            } else {
                msg = "Please cancel the receipt and then try canceling the Credit";
                if (!confirm(msg)){
                    return false;
                } else {
                    return false;
                }
            }
        });

        $('.m-tradingsg_order .actionBtnsDetail #generateReceipt').livequery('click', function (e){
            var title = "Create Receipt";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Receipt created successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        window.location.reload(true);
                    });
                }
            }
            var width = Math.min($(window).width() * 0.9, 500); // 90% of the viewport width or 600px max
            var height = Math.min($(window).height() * 0.9, 500); // 90% of the viewport height or 530px max
        
            Util.openFormInDialog.call(this, 'portalForm', title, width, height, expObj);
           
        });

        $('.room-order-table input.orderItemId, .room-order-table input.invoiceItemId, .room-order-table tbody tr input[id=fld_qty]').livequery('change', function (e){
            Util.showProgressInd();

            var parent = $(this).closest('tr');
            var qtyBalance = $('td.qtyBalance', parent).text();
            var qty = $('input[id=fld_qty]', parent).val();
            var cbObj = $('input.orderItemId', parent);
            var checked = cbObj.is(":checked") ? true : false;
            var qty = (qty != '') ? parseInt(qty) : parseInt(0);

            if(qty == 0 && checked){
                Util.alert('Please enter the qty')
            } else if(qty > qtyBalance && checked){
                Util.alert('The qty should not be more than the balance qty')
            } else {
                cpm.tradingsg.order.updateInvoiceAmount();
            }

            Util.hideProgressInd();
        });

        $('.m-tradingsg_order input.invoiceCode').livequery('click', function (e){
            Util.showProgressInd();
            var invoice_code   = $(this).val();
            var invoice_id = $(this).attr('invoice_id');
            var order_id   = $(this).attr('order_id');
            var checked    = $(this).attr('checked') ? 'checked' : '';
            var checkedVal = checked == 'checked' ? 1 : 0;

            var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=populateReceiptAmount&showHTML=0';
            $.get(url,{invoice_code: invoice_code ,checkedVal: checkedVal, invoice_id:invoice_id, order_id:order_id}, function(html){
                $('input[id=fld_amount]').val(html);
                Util.hideProgressInd();
            });
        });

        $('.m-tradingsg_order #generateSalesReturn').livequery('click', function (e){
            var title = "Create Sales Return";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Sales Return created successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        window.location.reload(true);
                    });
                }
            }
            Util.openFormInDialog.call(this, 'portalForm', title, 600, 500, expObj);
        });

        $('.cancelInvoice').livequery('click', function (e){
            var invoice_status = $(this).attr('invoice_status');

            if (invoice_status != 'Paid') {
                msg = "Do you like to cancel the Invoice?";
                if (!confirm(msg)){
                    return false;
                }
                else {
                    var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=cancelInvoice&showHTML=0';
                    Util.showProgressInd();
                    var invoice_code = $(this).attr('invoice_code');
                    var invoice_id   = $(this).attr('invoice_id');
                    $.get(url,{invoice_code: invoice_code, invoice_id:invoice_id}, function(html){

                        /* Checking for one or more receipt for the invoice */
                        if (html == 'Cannot cancel') {
                            alert ('Cancel the related receipts and then proceed canceling the invoice');
                            Util.hideProgressInd();
                        } else {
                            alert ('Invoice Cancelled Succesfully');
                            Util.hideProgressInd();
                            window.location.reload(true);
                        }
                    });
                }
            } else {
                msg = "Please cancel the receipt and then try canceling the Invoice";
                if (!confirm(msg)){
                    return false;
                } else {
                    return false;
                }
            }
        });


        $('#cancelOrderEdit').livequery('click', function(e){
            msg = "Please note related receipt,\n\n invoice will also be cancelled,\n\n Do you like to Cancel?";
            var order_id = $(this).attr('order_id');
            
            if (confirm(msg)){
                var title = "Add Notes";

                e.preventDefault();
                var expObj = {
                    url:"index.php?module=tradingsg_order&_spAction=CancelOrderNotes&order_id="+order_id+"&showHTML=0"
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

        $('.cancelReceipt').livequery('click', function (e){
            msg = "Do you like to cancel the Receipt?";
            var order_id = $("#record_id").val();
            if (!confirm(msg)){
                return false;
            }
            else {
                var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=cancelReceipt&showHTML=0';
                Util.showProgressInd();
                var receipt_id = $(this).attr('receipt_id');
                var order_id     = $(this).attr('order_id');
                $.get(url,{receipt_id: receipt_id, order_id:order_id}, function(html){

                    /* Checking for one or more receipt for the invoice */
                    if (html == 'Cannot cancel') {
                        alert ('Cancel the related receipts and then proceed canceling the invoice');
                        Util.hideProgressInd();
                    } else {
                        alert ('Receipt Cancelled Succesfully');
                        Util.hideProgressInd();
                        window.location.reload(true);
                    }
                });
            }
        });
    },

    checkAllCol: function(e){
        var colPos = $(this).parent().index();
        $('.room-order-table tbody tr').each(function(rowIndex, trObj) {
            var checkbox = $(trObj).find('td:eq(' + colPos + ') input');
            checkbox.attr('checked', 'checked');
        });
        cpm.tradingsg.order.updateInvoiceAmount();
    },

    uncheckAllCol: function(e){
        var colPos = $(this).parent().index();
        $('.room-order-table tbody tr').each(function(rowIndex, trObj) {
            var checkbox = $(trObj).find('td:eq(' + colPos + ') input');
            checkbox.removeAttr('checked');
        });
        $('.invoiceForm input[id=fld_invoice_amount]').val(0);
    },

    updateInvoiceAmount: function(){
        var amount = parseInt(0);
        $('.room-order-table tbody tr input[type=checkbox]:checked').each(function(){
            var parent = $(this).closest('tr');
            var valueObj = $('td.sellingPrice', parent);
            if(valueObj.text() != ''){
                var qtyObj = $(this).parents('tr').find('input[id=fld_qty]');
                var qty = (qtyObj.val() != '') ? parseInt(qtyObj.val()) : parseInt(0);

                amount += parseInt(valueObj.text()) * qty;
            }
        });
        $('.invoiceForm #fld_invoice_amount').html(amount);

        /* $('.room-order-table tbody tr input[name=qty]').livequery('change', function(){
            Util.showProgressInd();
            var parent = $(this).closest('tr');
            order_item_id = $(this).val();
            var checked    = $(this).attr('checked') ? 'checked' : '';
            var checkedVal = checked == 'checked' ? 1 : 0;

            var qtyObj = $(this).parents('tr').find('input[name=qty]');
            var qty = qtyObj.val();*/
            /*var priceObj = $('td.sellingPrice', parent);
            var price = priceObj.text();
            var valueObj = qty * price;
            if(valueObj != ''){
                amount += valueObj;
            }*/
         /*   var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=populateInvoiceAmount&showHTML=0';
            $.get(url,{order_item_id: order_item_id ,checkedVal: checkedVal, qty: qty}, function(html){
                $('.invoiceForm input[id=fld_invoice_amount]').val(html);
                Util.hideProgressInd();
            });
        });*/
    },

     checkAllDebit: function(e){
        var colPos = $(this).parent().index();
        $('.room-order-table tbody tr').each(function(rowIndex, trObj) {
            var checkbox = $(trObj).find('td:eq(' + colPos + ') input');
            checkbox.attr('checked', 'checked');
        });
        cpm.tradingsg.order.updatedebitNoteAmount();
    },

    uncheckAllDebit: function(e){
        var colPos = $(this).parent().index();
        $('.room-order-table tbody tr').each(function(rowIndex, trObj) {
            var checkbox = $(trObj).find('td:eq(' + colPos + ') input');
            checkbox.removeAttr('checked');
        });
        $('.debitNoteForm input[id=fld_invoice_amount]').val(0);
    },

    updatedebitNoteAmount: function(){
        var amount = parseInt(0);
        $('.room-order-table tbody tr input[type=checkbox]:checked').each(function(){
            var parent = $(this).closest('tr');
            var valueObj = $('td.sellingPrice', parent);
            if(valueObj.text() != ''){
                var qtyObj = $(this).parents('tr').find('input[id=fld_qty]');
                var qty = (qtyObj.val() != '') ? parseInt(qtyObj.val()) : parseInt(0);

                amount += parseInt(valueObj.text()) * qty;
            }
        });
        
        $('.debitNoteForm input[id=fld_invoice_amount]').val();
        $('.debitNoteForm #fld_invoice_amount').html(amount);

    
    },

     checkAllColling: function(e){
        var colPos = $(this).parent().index();
        $('.room-order-table tbody tr').each(function(rowIndex, trObj) {
            var checkbox = $(trObj).find('td:eq(' + colPos + ') input');
            checkbox.attr('checked', 'checked');
        });
        cpm.tradingsg.order.updatecreditNoteAmount();
    },

    uncheckAllColling: function(e){
        var colPos = $(this).parent().index();
        $('.room-order-table tbody tr').each(function(rowIndex, trObj) {
            var checkbox = $(trObj).find('td:eq(' + colPos + ') input');
            checkbox.removeAttr('checked');
        });
        $('.creditNoteForm input[id=fld_invoice_amount]').val(0);
    },

    updatecreditNoteAmount: function(){
        var amount = parseInt(0);
        $('.room-order-table tbody tr input[type=checkbox]:checked').each(function(){
            var parent = $(this).closest('tr');
            var valueObj = $('td.sellingPrice', parent);
            if(valueObj.text() != ''){
                var qtyObj = $(this).parents('tr').find('input[id=fld_qty]');
                var qty = (qtyObj.val() != '') ? parseInt(qtyObj.val()) : parseInt(0);

                amount += parseInt(valueObj.text()) * qty;
            }
        });
        
        $('.creditNoteForm input[id=fld_invoice_amount]').val();
        $('.creditNoteForm #fld_invoice_amount').html(amount);

        /* $('.room-order-table tbody tr input[name=qty]').livequery('change', function(){
            Util.showProgressInd();
            var parent = $(this).closest('tr');
            order_item_id = $(this).val();
            var checked    = $(this).attr('checked') ? 'checked' : '';
            var checkedVal = checked == 'checked' ? 1 : 0;

            var qtyObj = $(this).parents('tr').find('input[name=qty]');
            var qty = qtyObj.val();*/
            /*var priceObj = $('td.sellingPrice', parent);
            var price = priceObj.text();
            var valueObj = qty * price;
            if(valueObj != ''){
                amount += valueObj;
            }*/
         /*   var url = 'index.php?_topRm=finance&module=tradingsg_order&_spAction=populateInvoiceAmount&showHTML=0';
            $.get(url,{order_item_id: order_item_id ,checkedVal: checkedVal, qty: qty}, function(html){
                $('.invoiceForm input[id=fld_invoice_amount]').val(html);
                Util.hideProgressInd();
            });
        });*/
    }
    
}
