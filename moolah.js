jQuery(document).ready(function($){

    /* Checkout Form */
    $('form.checkout').on('checkout_place_order_moolah', function( event ) {
        if ($('#moolah-currency').val() != "") {                        
            return true;
        } else {
            alert("Please choose a currency!");
            return false;
        }
        
    });
    
});