(function () {
	'use strict';

	var chat = document.querySelector('.mucacran-chat');

	if (!chat || typeof MucacranWaAi === 'undefined') {
		return;
	}

	var selectedConversation = chat.getAttribute('data-selected-conversation');
	var messagesEl = document.getElementById('mucacran-chat-messages');
	var composer = document.getElementById('mucacran-chat-composer');
	var notice = composer ? composer.querySelector('.mucacran-chat__notice') : null;
	var useSuggestedReply = document.getElementById('mucacran-use-suggested-reply');
	var suggestedReplyText = document.getElementById('mucacran-suggested-reply-text');

	function scrollMessagesToBottom() {
		if (messagesEl) {
			messagesEl.scrollTop = messagesEl.scrollHeight;
		}
	}

	function escapeHtml(value) {
		var div = document.createElement('div');
		div.textContent = value || '';
		return div.innerHTML;
	}

	function renderMessages(messages) {
		if (!messagesEl) {
			return;
		}

		if (!messages.length) {
			messagesEl.innerHTML = '<p class="mucacran-chat__empty">Messages will appear here.</p>';
			return;
		}

		messagesEl.innerHTML = messages.map(function (message) {
			var status = message.delivery_status ? ' - ' + escapeHtml(message.delivery_status) : '';
			var error = message.error_message ? '<span class="mucacran-message__error">' + escapeHtml(message.error_message) + '</span>' : '';

			return '<div class="mucacran-message mucacran-message--' + escapeHtml(message.direction) + '">' +
				'<div class="mucacran-message__bubble">' +
				'<p>' + escapeHtml(message.message_body) + '</p>' +
				'<span class="mucacran-message__meta">' + escapeHtml(message.created_at) + status + '</span>' +
				error +
				'</div>' +
				'</div>';
		}).join('');

		scrollMessagesToBottom();
	}

	function post(action, data) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', MucacranWaAi.nonce);

		Object.keys(data).forEach(function (key) {
			body.append(key, data[key]);
		});

		return fetch(MucacranWaAi.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: body.toString()
		}).then(function (response) {
			return response.json();
		});
	}

	function refreshMessages() {
		if (!selectedConversation) {
			return;
		}

		post('mucacran_wa_ai_get_messages', {
			conversation_id: selectedConversation
		}).then(function (result) {
			if (result.success) {
				renderMessages(result.data.messages || []);
			}
		});
	}

	if (composer) {
		composer.addEventListener('submit', function (event) {
			event.preventDefault();

			var textarea = composer.querySelector('textarea[name="message"]');
			var button = composer.querySelector('button[type="submit"]');
			var message = textarea ? textarea.value.trim() : '';

			if (!message || !selectedConversation) {
				return;
			}

			button.disabled = true;
			button.textContent = MucacranWaAi.i18n.sending;
			notice.textContent = '';

			post('mucacran_wa_ai_send_message', {
				conversation_id: selectedConversation,
				message: message
			}).then(function (result) {
				if (result.success) {
					textarea.value = '';
					refreshMessages();
					notice.textContent = result.data.message || '';
				} else {
					notice.textContent = (result.data && result.data.message) ? result.data.message : MucacranWaAi.i18n.error;
				}
			}).catch(function () {
				notice.textContent = MucacranWaAi.i18n.error;
			}).finally(function () {
				button.disabled = false;
				button.textContent = MucacranWaAi.i18n.send;
			});
		});
	}

	if (useSuggestedReply && suggestedReplyText && composer) {
		useSuggestedReply.addEventListener('click', function () {
			var textarea = composer.querySelector('textarea[name="message"]');

			if (textarea) {
				textarea.value = suggestedReplyText.textContent.trim();
				textarea.focus();
			}
		});
	}

	scrollMessagesToBottom();
})();
