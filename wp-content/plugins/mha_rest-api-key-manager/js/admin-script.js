document.addEventListener('DOMContentLoaded', function () {
	const popup = document.getElementById('api-key-popup');
	const closePopupButton = document.getElementById('close-popup');
	const copyButton = document.getElementById('copy-api-key');
	const apiKeyElement = document.getElementById('generated-api-key');

	if (popup) {
		// Show the popup if it exists.
		popup.style.display = 'block';

		// Close popup on button click.
		closePopupButton.addEventListener('click', function () {
			popup.style.display = 'none';
		});

		// Copy API key to clipboard.
		copyButton.addEventListener('click', function () {
			const apiKey = apiKeyElement.textContent;

			// Check if the Clipboard API is supported.
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(apiKey)
					.then(function () {
						alert('API key copied to clipboard!');
					})
					.catch(function (err) {
						console.error('Failed to copy API key:', err);
						alert('Failed to copy API key. Please try again.');
					});
			} else {
				// Fallback: Create a temporary input element to copy text.
				const tempInput = document.createElement('textarea');
				tempInput.value = apiKey;
				tempInput.style.position = 'absolute';
				tempInput.style.left = '-9999px'; // Move out of view
				document.body.appendChild(tempInput);
				tempInput.select();
				try {
					document.execCommand('copy'); // Copy the text
					alert('API key copied to clipboard!');
				} catch (err) {
					console.error('Failed to copy API key:', err);
					alert('Failed to copy API key. Please try again.');
				}
				document.body.removeChild(tempInput); // Remove the temporary element
			}
		});
	}
});
