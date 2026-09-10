/**
 * Prescreen: sync GF hidden "Prescreen Answers" from collection localStorage (CSV + JSON cookies for PHP merge tags).
 */
(function ($) {
	'use strict';

	function orderedCsv(sectionPages, answers) {
		var yes = [];
		var no = [];
		var i;
		var p;
		var v;
		for (i = 0; i < sectionPages.length; i++) {
			p = sectionPages[i];
			v = answers[String(p)];
			if (v === 'yes') {
				yes.push(p);
			} else if (v === 'no') {
				no.push(p);
			}
		}
		return yes.concat(no).join(',');
	}

	function prescreenAnswersCookieName(formId) {
		return 'mha_prescreen_answers_' + formId;
	}

	function scPrescreenJsonCookieName(screenId, formId) {
		return 'mha_sc_prescreen_json_' + screenId + '_' + formId;
	}

	function buildScPrescreenJsonPayload(sectionPages, answers) {
		var arr = [];
		var i;
		var page;
		var v;
		var answer;
		for (i = 0; i < sectionPages.length; i++) {
			page = parseInt(sectionPages[i], 10);
			if (!page) {
				continue;
			}
			v = answers[String(page)];
			answer = v === 'yes' ? 1 : (v === 'no' ? 0 : null);
			if (answer === null) {
				continue;
			}
			arr.push({ page: page, answer: answer });
		}
		arr.sort(function (a, b) {
			return a.page - b.page;
		});
		return arr.length ? JSON.stringify(arr) : '';
	}

	function setScPrescreenJsonCookie(screenId, formId, sectionPages, answers) {
		var sid = parseInt(screenId, 10);
		var fid = parseInt(formId, 10);
		if (!sid || !fid || !answers) {
			return;
		}
		var json = buildScPrescreenJsonPayload(sectionPages, answers);
		if (!json) {
			return;
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = scPrescreenJsonCookieName(sid, fid) + '=' + encodeURIComponent(json) + '; Max-Age=86400; Path=/; SameSite=Lax' + secure;
	}

	function clearScPrescreenJsonCookie(screenId, formId) {
		var sid = parseInt(screenId, 10);
		var fid = parseInt(formId, 10);
		if (!sid || !fid) {
			return;
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = scPrescreenJsonCookieName(sid, fid) + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
	}

	function setPrescreenAnswersCookie(formId, csv) {
		if (!csv) {
			return;
		}
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = prescreenAnswersCookieName(formId) + '=' + encodeURIComponent(csv) + '; Max-Age=86400; Path=/; SameSite=Lax' + secure;
	}

	function clearPrescreenAnswersCookie(formId) {
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = prescreenAnswersCookieName(formId) + '=; Max-Age=0; Path=/; SameSite=Lax' + secure;
	}

	function loadAnswers(storageKey) {
		try {
			var raw = localStorage.getItem(storageKey);
			if (!raw) {
				return null;
			}
			var st = JSON.parse(raw);
			if (!st || st.v !== 1 || !st.answers || typeof st.answers !== 'object') {
				return null;
			}
			return st.answers;
		} catch (e) {
			return null;
		}
	}

	function syncScreenCollectionUserCookieFromStorage() {
		try {
			var scUid = window.localStorage.getItem('mha_screen_collection_user_id');
			if (!scUid || !String(scUid).trim()) {
				return;
			}
			var secure = window.location.protocol === 'https:' ? '; Secure' : '';
			document.cookie = 'mha_screen_collection_uid=' + encodeURIComponent(String(scUid).trim()) + '; Max-Age=86400; Path=/; SameSite=Lax' + secure;
		} catch (e) {}
	}

	function run() {
		var cfg = window.mhaPrescreenFormNav;
		if (!cfg || !cfg.formId || !cfg.fieldId || !cfg.sectionPages || !cfg.sectionPages.length) {
			return;
		}
		syncScreenCollectionUserCookieFromStorage();
		var formId = cfg.formId;
		var fid = cfg.fieldId;
		var $input = $('#input_' + formId + '_' + fid);
		if (!$input.length) {
			$input = $('#gform_' + formId).find('input[name="input_' + fid + '"]');
		}
		if (!$input.length) {
			return;
		}

		var csvFromField = $input.val() ? String($input.val()).trim() : '';
		var answers = loadAnswers(cfg.storageKey);
		var csvFromLs = answers ? orderedCsv(cfg.sectionPages, answers) : '';
		if (csvFromField) {
			return;
		}
		if (csvFromLs) {
			$input.val(csvFromLs);
			setPrescreenAnswersCookie(formId, csvFromLs);
			if (cfg.screenId && answers) {
				setScPrescreenJsonCookie(cfg.screenId, formId, cfg.sectionPages, answers);
			}
			return;
		}
		clearPrescreenAnswersCookie(formId);
		if (cfg.screenId) {
			clearScPrescreenJsonCookie(cfg.screenId, formId);
		}
	}

	$(function () {
		run();
	});

	$(document).on('gform_post_render', function (e, formId) {
		var cfg = window.mhaPrescreenFormNav;
		if (!cfg || parseInt(formId, 10) !== parseInt(cfg.formId, 10)) {
			return;
		}
		run();
	});
})(jQuery);
