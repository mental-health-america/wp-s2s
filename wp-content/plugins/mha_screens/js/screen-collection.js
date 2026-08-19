jQuery(function ($) {

	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
	}

	var PREENSCREENS_SESSION_KEY = 'mha_screen_collection_prescreens_opened';

	/**
	 * True when every prescreen widget on this collection page has a saved submitted state
	 * (same localStorage keys as initScreenCollectionPrescreen: mha_screen_prescreen_{screen}_{form}).
	 */
	function hasCompletedAllCollectionPrescreens() {
		var $items = $('#screen-collection-prescreens-wrap .mha-screen-collection-prescreen');
		if (!$items.length) {
			return false;
		}
		var allDone = true;
		$items.each(function () {
			var sid = $(this).attr('data-screen-id');
			var fid = $(this).attr('data-form-id');
			if (!sid || !fid) {
				allDone = false;
				return false;
			}
			var key = 'mha_screen_prescreen_' + sid + '_' + fid;
			var raw;
			try {
				raw = localStorage.getItem(key);
			} catch (e) {
				allDone = false;
				return false;
			}
			if (!raw) {
				allDone = false;
				return false;
			}
			try {
				var st = JSON.parse(raw);
				if (!st || st.v !== 1 || !st.submitted) {
					allDone = false;
					return false;
				}
			} catch (e2) {
				allDone = false;
				return false;
			}
		});
		return allDone;
	}

	function prescreenGatingRefresh() {
		var $wrap = $('#screen-collection-prescreens-wrap');
		if (!$wrap.length) {
			return;
		}
		var userId = localStorage.getItem('mha_screen_collection_user_id');
		var $start = $wrap.find('.screen-collection-prescreens-start');
		var $inner = $wrap.find('.screen-collection-prescreens-inner');
		if (!userId) {
			$wrap.addClass('d-none').attr('aria-hidden', 'true');
			$start.addClass('d-none').attr('aria-expanded', 'false');
			$inner.addClass('d-none');
			return;
		}
		$wrap.removeClass('d-none').attr('aria-hidden', 'false');
		var prescreensDone = hasCompletedAllCollectionPrescreens();
		if (prescreensDone) {
			try {
				sessionStorage.setItem(PREENSCREENS_SESSION_KEY, '1');
			} catch (errSync) {}
		}
		if (sessionStorage.getItem(PREENSCREENS_SESSION_KEY) === '1' || prescreensDone) {
			$start.addClass('d-none').attr('aria-expanded', 'true');
			$inner.removeClass('d-none');
		} else {
			$start.removeClass('d-none').attr('aria-expanded', 'false');
			$inner.addClass('d-none');
		}
	}

	function updateGravityFormsScreenCollectionField() {
		var urlParams = new URLSearchParams(window.location.search);
		if (!urlParams.has('org')) {
			return;
		}

		var org_id = localStorage.getItem('mha_screen_collection_org_id') || '';
		var user_id = localStorage.getItem('mha_screen_collection_user_id') || '';

		if (!org_id && !user_id) {
			return;
		}

		var forms = document.querySelectorAll('form[id^="gform_"]');
		if (forms.length === 0) {
			return;
		}

		forms.forEach(function (form) {
			var hiddenInputs = form.querySelectorAll('input[type="hidden"]');
			hiddenInputs.forEach(function (input) {
				var currentValue = input.value || '';
				if (currentValue === 'scd_blank') {
					var newValue = org_id + '&&&' + user_id;
					input.value = newValue;
					input.dispatchEvent(new Event('change', { bubbles: true }));
					input.dispatchEvent(new Event('input', { bubbles: true }));
					if (typeof jQuery !== 'undefined') {
						jQuery(input).val(newValue).trigger('change');
						var formId = form.id.replace('gform_', '');
						if (formId) {
							var inputId = input.id || input.name;
							if (inputId) {
								var fieldIdMatch = inputId.match(/input_(\d+)(?:_(\d+))?/);
								if (fieldIdMatch) {
									var fieldId = fieldIdMatch[2] ? fieldIdMatch[1] + '.' + fieldIdMatch[2] : fieldIdMatch[1];
									jQuery(document).trigger('gform_input_change', [input, formId, fieldId]);
								}
							}
						}
					}
				}
			});
		});
	}

	updateGravityFormsScreenCollectionField();
	$(document).on('gform_post_render', function () {
		updateGravityFormsScreenCollectionField();
	});

	$('.spinner-border').hide();
	$('.screen-collection-user').removeClass('d-none');

	var $collectionForm = $('.screen-collection-user');
	if ($collectionForm.length > 0) {
		var userId = localStorage.getItem('mha_screen_collection_user_id');
		if (userId) {
			showUserIdMessage(userId);
		} else {
			handleFormSubmission();
		}
	}

	prescreenGatingRefresh();

	function handleFormSubmission() {
		$collectionForm.on('submit', function (e) {
			e.preventDefault();

			var $userInput = $(this).find('input[name="user_id"]');
			var user_id = $userInput.val().trim();

			var $orgInput = $(this).find('input[name="org"]');
			var $orgIdInput = $(this).find('input[name="org_id"]');
			var $screenIdsInput = $(this).find('input[name="screen_ids"]');
			var $screenCollectionInput = $(this).find('input[name="screen_collection"]');
			var org = $orgInput.length > 0 ? $orgInput.val() : '';
			var org_id = $orgIdInput.length > 0 ? $orgIdInput.val() : '';
			var screen_ids = $screenIdsInput.length > 0 ? $screenIdsInput.val() : '';
			var screen_collection = $screenCollectionInput.length > 0 ? $screenCollectionInput.val() : '';

			if (user_id) {
				localStorage.setItem('mha_screen_collection_user_id', user_id);
				setScreenCollectionUserCookie(user_id);
				if (org) {
					localStorage.setItem('mha_screen_collection_org', org);
				}
				if (org_id) {
					localStorage.setItem('mha_screen_collection_org_id', org_id);
				}
				if (screen_ids) {
					localStorage.setItem('mha_screen_collection_screen_ids', screen_ids);
				}
				if (screen_collection) {
					localStorage.setItem('mha_screen_collection', screen_collection);
				}

				var $formToReplace = $('.screen-collection-user');
				showUserIdMessage(user_id, $formToReplace);
			}
		});
	}

	function showUserIdMessage(userId, $formElement) {
		var $formToReplace = $formElement || $('.screen-collection-user');
		if ($formToReplace.length === 0) {
			return;
		}

		setScreenCollectionUserCookie(userId);

		var messageHtml = '<div class="screen-collection-user-message">' +
			'<p><strong>User ID:</strong> ' + escapeHtml(userId) + ' <a href="#" class="screen-collection-reset small button tiny gray round" data-reset="true">reset</a></p>' +
			'</div>';

		$('#screenings-list').removeClass('d-none');
		$('#screenings-list').attr('aria-hidden', 'false');

		$formToReplace.replaceWith(messageHtml);

		var screenOrderEnabled = $('#screenings-list').attr('data-screen-order') === 'true';
		if (screenOrderEnabled) {
			reloadScreeningsListWithUserId(userId);
		}

		prescreenGatingRefresh();
	}

	function reloadScreeningsListWithUserId(userId) {
		var orgId = localStorage.getItem('mha_screen_collection_org_id') || '';
		var urlParams = new URLSearchParams(window.location.search);
		var screenOrderParam = $('#screenings-list').attr('data-screen-order') || 'false';
		var referrerParam = urlParams.get('ref') || '';
		var iframeModeParam = urlParams.get('iframe') || 'false';

		var screenIds = [];
		$('#screenings-list .screen-item').each(function () {
			var screenId = $(this).attr('data-screen-id');
			if (screenId) {
				screenIds.push(screenId);
			}
		});

		if (screenIds.length === 0) {
			return;
		}

		var collectionIdAttr = $('#screenings-list').attr('data-collection-id') || '';

		$.ajax({
			type: 'POST',
			url: mhaScreenCollection.ajaxurl,
			data: {
				action: 'reload_screen_collection_list',
				screens: screenIds.join(','),
				screen_order: screenOrderParam,
				org_id: orgId,
				user_id: userId,
				referrer: referrerParam,
				iframe_mode: iframeModeParam,
				collection_id: collectionIdAttr
			},
			success: function (response) {
				console.log('=== Screen Collection GFAPI Debug ===');
				console.log('Full AJAX Response:', response);

				if (response.success) {
					if (response.data && response.data.debug) {
						console.log('GFAPI Debug Data:', response.data.debug);
						if (typeof response.data.debug === 'object') {
							for (var screenId in response.data.debug) {
								if (response.data.debug.hasOwnProperty(screenId)) {
									console.log('Screen ID ' + screenId + ':', response.data.debug[screenId]);
								}
							}
						}
					}
					if (response.data.html) {
						var $newList = $(response.data.html);
						$('#screenings-list').replaceWith($newList);
						$newList.removeClass('d-none');
						$newList.attr('aria-hidden', 'false');
					}
				} else {
					console.error('AJAX returned success:false', response);
				}
				console.log('=== End GFAPI Debug ===');
			},
			error: function (xhr, status, error) {
				console.error('Screen Collection AJAX Error', status, error, xhr.responseText);
			}
		});
	}

	function clearPrescreenBridgeCookies() {
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = 'mha_screen_collection_uid=; Max-Age=0; Path=/; SameSite=Lax' + secure;
		if (typeof document.cookie !== 'string' || !document.cookie) {
			return;
		}
		document.cookie.split(';').forEach(function (part) {
			var idx = part.indexOf('=');
			var name = (idx === -1 ? part : part.slice(0, idx)).trim();
			if (name.indexOf('mha_prescreen_answers_') === 0 || name.indexOf('mha_sc_prescreen_json_') === 0) {
				document.cookie = name + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
			}
		});
	}

	function setScreenCollectionUserCookie(userId) {
		var uid = String(userId == null ? '' : userId).trim();
		if (!uid) {
			return;
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = 'mha_screen_collection_uid=' + encodeURIComponent(uid) + '; Max-Age=86400; Path=/; SameSite=Lax' + secure;
	}

	function buildScPrescreenJsonPayload(answers) {
		var arr = [];
		if (!answers || typeof answers !== 'object') {
			return '';
		}
		Object.keys(answers).forEach(function (k) {
			var page = parseInt(k, 10);
			if (!page) {
				return;
			}
			var v = answers[k];
			var answer = v === 'yes' ? 1 : (v === 'no' ? 0 : null);
			if (answer === null) {
				return;
			}
			arr.push({ page: page, answer: answer });
		});
		arr.sort(function (a, b) {
			return a.page - b.page;
		});
		if (!arr.length) {
			return '';
		}
		return JSON.stringify(arr);
	}

	function setScPrescreenJsonCookie(screenId, formId, answers) {
		var sid = parseInt(screenId, 10);
		var fid = parseInt(formId, 10);
		if (!sid || !fid) {
			return;
		}
		var json = buildScPrescreenJsonPayload(answers);
		if (!json) {
			return;
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		var name = 'mha_sc_prescreen_json_' + sid + '_' + fid;
		document.cookie = name + '=' + encodeURIComponent(json) + '; Max-Age=86400; Path=/; SameSite=Lax' + secure;
	}

	/** Same CSV as prescreen-form-nav / PHP cookie bridge so first GET to a screen sees correct pager order. */
	function setPrescreenAnswersCookieForForm(screenId, formId, answers, pagesArr) {
		var fid = parseInt(formId, 10);
		if (!fid || !answers || !pagesArr || !pagesArr.length) {
			return;
		}
		var yes = [];
		var no = [];
		pagesArr.forEach(function (p) {
			var v = answers[String(p.page)];
			if (v === 'yes') {
				yes.push(p.page);
			} else if (v === 'no') {
				no.push(p.page);
			}
		});
		var csv = yes.concat(no).join(',');
		if (!csv) {
			return;
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = 'mha_prescreen_answers_' + fid + '=' + encodeURIComponent(csv) + '; Max-Age=86400; Path=/; SameSite=Lax' + secure;
		setScPrescreenJsonCookie(screenId, fid, answers);
	}

	$(document).on('click', '.screen-collection-reset', function (e) {
		e.preventDefault();
		clearPrescreenBridgeCookies();
		localStorage.removeItem('mha_screen_collection_user_id');
		localStorage.removeItem('mha_screen_collection_org');
		localStorage.removeItem('mha_screen_collection_org_id');
		localStorage.removeItem('mha_screen_collection_screen_ids');
		localStorage.removeItem('mha_screen_collection');
		try {
			Object.keys(localStorage).forEach(function (key) {
				if (key.indexOf('mha_screen_prescreen_') === 0) {
					localStorage.removeItem(key);
				}
			});
		} catch (err) {}
		try {
			sessionStorage.removeItem(PREENSCREENS_SESSION_KEY);
		} catch (err2) {}
		window.location.reload();
	});

	$(document).on('click', '.screen-collection-prescreens-start', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $wrap = $('#screen-collection-prescreens-wrap');
		try {
			sessionStorage.setItem(PREENSCREENS_SESSION_KEY, '1');
		} catch (err) {}
		$btn.addClass('d-none').attr('aria-expanded', 'true');
		$wrap.find('.screen-collection-prescreens-inner').removeClass('d-none');
	});

	function initScreenCollectionPrescreen() {
		$('.mha-screen-collection-prescreen').each(function () {
			var $root = $(this);
			var $json = $root.find('script.mha-prescreen-config[type="application/json"]').first();
			if (!$json.length) {
				return;
			}
			var config;
			try {
				config = JSON.parse($json.text());
			} catch (err) {
				return;
			}
			var storageKey = config.storageKey;
			var pages = config.pages || [];
			var formIdAttr = parseInt($root.attr('data-form-id'), 10) || 0;
			var screenIdAttr = parseInt($root.attr('data-screen-id'), 10) || 0;
			var $qForm = $root.find('.mha-prescreen-form');
			var $continue = $root.find('.mha-prescreen-continue');

			function saveState(answers, submitted) {
				try {
					localStorage.setItem(storageKey, JSON.stringify({ v: 1, answers: answers, submitted: !!submitted }));
				} catch (e) {}
			}

			function loadState() {
				try {
					var raw = localStorage.getItem(storageKey);
					if (!raw) {
						return null;
					}
					return JSON.parse(raw);
				} catch (e2) {
					return null;
				}
			}

			function applyAnswersToRadios(answers) {
				Object.keys(answers).forEach(function (k) {
					var v = answers[k];
					$qForm.find('input[name="prescreen_page_' + k + '"][value="' + v + '"]').prop('checked', true);
				});
			}

			function showContinue() {
				$qForm.addClass('d-none').attr('aria-hidden', 'true');
				$continue.removeClass('d-none').removeAttr('hidden').attr('aria-hidden', 'false');
			}

			var st = loadState();
			if (st && st.v === 1 && st.answers) {
				applyAnswersToRadios(st.answers);
				if (st.submitted) {
					showContinue();
					setPrescreenAnswersCookieForForm(screenIdAttr, formIdAttr, st.answers, pages);
				}
			}

			$qForm.on('submit', function (e) {
				e.preventDefault();
				var answers = {};
				var valid = true;
				pages.forEach(function (p) {
					var name = 'prescreen_page_' + p.page;
					var checked = $qForm.find('input[name="' + name + '"]:checked').val();
					if (!checked) {
						valid = false;
					}
					answers[String(p.page)] = checked || '';
				});
				if (!valid) {
					return;
				}
				showContinue();
				saveState(answers, true);
				setPrescreenAnswersCookieForForm(screenIdAttr, formIdAttr, answers, pages);
			});
		});
	}

	/**
	 * Build prescreen JSON [{page,answer},…] from collection prescreen localStorage answers (yes=1, no=0).
	 *
	 * @param {Object} answers Keys are GF page numbers as strings.
	 * @returns {string} JSON or ''.
	 */
	function mhaBuildPrescreenJsonFromAnswers(answers) {
		var arr = [];
		if (!answers || typeof answers !== 'object') {
			return '';
		}
		Object.keys(answers).forEach(function (k) {
			var page = parseInt(k, 10);
			if (!page) {
				return;
			}
			var v = answers[k];
			var answer = v === 'yes' ? 1 : (v === 'no' ? 0 : null);
			if (answer === null) {
				return;
			}
			arr.push({ page: page, answer: answer });
		});
		arr.sort(function (a, b) {
			return a.page - b.page;
		});
		return arr.length ? JSON.stringify(arr) : '';
	}

	function mhaGetPrescreenJsonCookie(screenId, formId) {
		var name = 'mha_sc_prescreen_json_' + screenId + '_' + formId + '=';
		var parts = document.cookie.split(';');
		var i;
		var c;
		for (i = 0; i < parts.length; i++) {
			c = parts[i].trim();
			if (c.indexOf(name) === 0) {
				try {
					return decodeURIComponent(c.substring(name.length));
				} catch (e) {
					return '';
				}
			}
		}
		return '';
	}

	/**
	 * Fill GF hidden defaults still set to {sc_prescreen_answers} / {sc_user} (gform_page_loaded / gform_post_render).
	 *
	 * @param {number|string} form_id
	 * @param {number|string} current_page
	 */
	function mhaFillScreenCollectionPrescreenPlaceholders(form_id, current_page) {
		var cfg = window.mhaScreenCollection;
		var fid = parseInt(form_id, 10);
		if (!fid) {
			return;
		}
		var $ctx = $('#gform_wrapper_' + fid);
		if (!$ctx.length) {
			$ctx = $(document);
		}

		var screenId = 0;
		if (cfg && parseInt(cfg.screenPostId, 10)) {
			screenId = parseInt(cfg.screenPostId, 10);
		}
		if (!screenId && document.body && document.body.className) {
			var m = document.body.className.match(/\bpostid-(\d+)\b/);
			if (m) {
				screenId = parseInt(m[1], 10) || 0;
			}
		}

		var jsonPayload = '';
		if (screenId && fid) {
			try {
				var key = 'mha_screen_prescreen_' + screenId + '_' + fid;
				var raw = window.localStorage.getItem(key);
				if (raw) {
					var st = JSON.parse(raw);
					if (st && st.v === 1 && st.answers && st.submitted) {
						jsonPayload = mhaBuildPrescreenJsonFromAnswers(st.answers);
					}
				}
			} catch (e1) {}
			if (!jsonPayload) {
				jsonPayload = mhaGetPrescreenJsonCookie(screenId, fid);
			}
		}

		$ctx.find('input[type="hidden"]').each(function () {
			var $inp = $(this);
			var v = $.trim($inp.val());
			if (v === '{sc_prescreen_answers}') {
				if (jsonPayload) {
					$inp.val(jsonPayload).trigger('change');
				}
			} else if (v === '{sc_user}') {
				if (cfg && cfg.scUserHash) {
					$inp.val(cfg.scUserHash).trigger('change');
					return;
				}
				var uid = '';
				try {
					uid = (window.localStorage.getItem('mha_screen_collection_user_id') || '').trim();
				} catch (e2) {}
				if (uid && cfg && cfg.ajaxurl && cfg.scUserNonce) {
					$.post(cfg.ajaxurl, {
						action: 'mha_screen_collection_sc_user_hash',
						nonce: cfg.scUserNonce,
						user_id: uid
					}).done(function (res) {
						if (res && res.success && res.data && res.data.hash && $.trim($inp.val()) === '{sc_user}') {
							$inp.val(res.data.hash).trigger('change');
						}
					});
				}
			}
		});
	}

	$(document).on('gform_page_loaded gform_post_render', function (event, form_id, current_page) {
		mhaFillScreenCollectionPrescreenPlaceholders(form_id, current_page);
	});

	initScreenCollectionPrescreen();
});
