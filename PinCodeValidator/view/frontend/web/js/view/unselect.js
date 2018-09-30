require(['jquery'],function($) {
    $(document).ready(function () {
        $("#co-payment-form").click(function () {
            $(":radio").click(function () {
                window.payment.method = $(this).attr('id');
                if (!($(this).attr('id') === 'cashondelivery')) {
                    $('#cod-fee-block').css('display', 'none');
                }
            });
        });
    });
});