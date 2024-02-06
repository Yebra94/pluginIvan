(function ($) {
    "use strict";
    $(document).ready(function () {
        $(".loading-indicator").hide();
        $('#multistep-form').submit(function (event) {
            event.preventDefault();
            var formData = $(this).serialize();
            $(".loading-indicator").show();
            $.ajax({
                type: 'POST',
                url: ajaxurl.ajaxurl,
                data: formData,
                success: function (response) {
                   //replace the form with the response
                    $('#multistep-form').replaceWith(response.html);
                },
                error: function (xhr, status, error) {
                    // Handle errors
                    console.log(error);
                },
                complete: function () {
                    $(".loading-indicator").hide();
                }
            });
        });
    });
})(jQuery);