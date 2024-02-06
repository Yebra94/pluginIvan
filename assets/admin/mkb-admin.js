(function ($) {
	"use strict";
  $(document).ready(function () {
    $('#add-row-extra-services').on('click', function () {
			var row = $('#extra-services-admin .empty-row-extra-services.screen-reader-text').clone(true);
			row.removeClass('empty-row-extra-services screen-reader-text');
			row.insertBefore('#extra-services-admin #repeatable-fieldset-one-extra-service tbody>tr:last');
			return false;
		});
		$('.remove-row-extra-services').on('click', function () {
			if (confirm('Are You Sure , Remove this row ? \n\n 1. Ok : To Remove . \n 2. Cancel : To Cancel .')) {
				$(this).parents('tr').remove();
			} else {
				return false;
			}
		});
  });
})(jQuery);