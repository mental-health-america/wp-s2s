(function ($) {
	'use strict';

	function showModule($root, index) {
		var idx = String(index);
		$root.find('.collection-module-panel').each(function () {
			var $panel = $(this);
			var match = String($panel.data('module-index')) === idx;
			$panel.prop('hidden', !match);
		});
		$root.find('.collection-module-tab').each(function () {
			var $tab = $(this);
			var match = String($tab.data('module-index')) === idx;
			$tab.attr('aria-selected', match ? 'true' : 'false');
			$tab.toggleClass('teal', match);
			$tab.toggleClass('mint', !match);
		});
		var $select = $root.find('#collection-module-select');
		if ($select.length && String($select.val()) !== idx) {
			$select.val(idx);
		}
	}

	$(function () {
		$('.collection-module-answers').each(function () {
			var $root = $(this);
			var defaultIndex = $root.data('default-index');
			if (typeof defaultIndex === 'undefined' || defaultIndex === '') {
				defaultIndex = 0;
			}
			showModule($root, defaultIndex);

			$root.on('click', '.collection-module-tab', function (e) {
				e.preventDefault();
				showModule($root, $(this).data('module-index'));
			});

			$root.on('change', '#collection-module-select', function () {
				showModule($root, $(this).val());
			});
		});
	});
})(jQuery);
