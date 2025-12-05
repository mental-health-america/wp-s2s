=== MHA - REST API Key Manager ===
A simple plugin to add manage multiple API keys directly from the WordPress admin dashboard.

== Description ==

### Key Features:
- **Multiple API Keys**: Create and manage multiple API keys with custom names.
- **Secure API Key Storage**: API keys are hashed and securely stored in the WordPress database.
- **Single Display for Security**: API keys are shown only once after creation.
- **Admin Interface**: Manage API keys with a user-friendly admin page.
- **Copy to Clipboard Popup**: Easily copy generated API keys with a built-in popup.

The plugin is lightweight and integrates seamlessly with WordPress.

== Installation ==

1. Download the plugin ZIP file.
2. Go to your WordPress admin dashboard and navigate to **Plugins > Add New**.
3. Click on the **Upload Plugin** button and select the ZIP file.
4. Click **Install Now** and then activate the plugin.
5. Navigate to **API Keys** in the admin menu to start managing your API keys.

== Usage ==

1. **Generate an API Key**:
   - Go to **API Keys** in the WordPress admin menu.
   - Enter a name for the API key and click "Generate API Key".
   - The API key will appear in a popup. Copy it immediately, as it will not be displayed again.

2. **Delete API Keys**:
   - To revoke access, delete an API key from the **API Keys** admin page.

== Frequently Asked Questions ==

= How are API keys stored? =
API keys are hashed using PHP's `password_hash` function and stored securely in the WordPress database. The raw key is only shown once upon creation.

= What happens if I lose an API key? =
If you lose an API key, you must generate a new one. The plugin does not store raw API keys for security reasons.

= How do I authenticate a REST API request? =
Include the API key in an `X-Api-Key` header. (See `mha_shard` plugin.)

= Can I create multiple API keys? =
Yes, you can generate multiple API keys with custom names and manage them from the admin interface.

== Screenshots ==

1. **API Key Management Interface**  
   Manage API keys with a simple interface, including options to create and delete keys.

2. **Generated API Key Popup**  
   Popup showing the API key after generation, with an option to copy it to the clipboard.
