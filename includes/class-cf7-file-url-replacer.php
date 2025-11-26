<?php
/**
 * Main plugin class
 *
 * @package CF7_File_URL_Replacer
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * CF7_File_URL_Replacer Class
 */
class CF7_File_URL_Replacer
{

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->version = CF7_FILE_URL_REPLACER_VERSION;
	}

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function init()
	{
		add_action('wpcf7_before_send_mail', array($this, 'replace_file_fields_with_urls'), 10, 3);
		add_filter('plugin_action_links_' . CF7_FILE_URL_REPLACER_BASENAME, array($this, 'add_action_links'));
	}

	/**
	 * Replace all file field tags with clickable URLs
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form object.
	 * @param bool              $abort        Whether to abort mail sending.
	 * @param WPCF7_Submission  $submission   The submission object.
	 * @return void
	 */
	public function replace_file_fields_with_urls($contact_form, &$abort, $submission)
	{

		// Load required WordPress files
		$this->load_media_dependencies();

		// Get uploaded files
		$uploaded_files = $submission->uploaded_files();

		// Check if there are any uploaded files
		if (empty($uploaded_files)) {
			return;
		}

		// Get mail properties
		$mail = $contact_form->prop('mail');
		$mail_body = $mail['body'];

		// Loop through all uploaded files
		foreach ($uploaded_files as $field_name => $files) {

			// Skip if no files uploaded for this field
			if (empty($files)) {
				continue;
			}

			$file_links = $this->process_uploaded_files($files);

			// Replace the file field tag with formatted links
			if (!empty($file_links)) {
				$files_html = implode('<br>', $file_links);
				$mail_body = str_replace('[' . $field_name . ']', $files_html, $mail_body);
			} else {
				// If no files were successfully uploaded, show fallback message
				$no_files_msg = '<em style="color: #999;">' . esc_html__('No files uploaded', 'cf7-file-url-replacer') . '</em>';
				$mail_body = str_replace('[' . $field_name . ']', $no_files_msg, $mail_body);
			}
		}

		// Update mail body with replaced content
		$mail['body'] = $mail_body;

		// Update mail properties
		$contact_form->set_properties(array('mail' => $mail));
	}

	/**
	 * Process uploaded files and save to media library
	 *
	 * @param mixed $files Single file path or array of file paths.
	 * @return array Array of formatted HTML links.
	 */
	private function process_uploaded_files($files)
	{
		$file_links = array();

		// Handle single or multiple files
		$files_array = is_array($files) ? $files : array($files);

		// Process each file
		foreach ($files_array as $file_path) {

			// Verify file exists
			if (!file_exists($file_path)) {
				continue;
			}

			// Upload file to media library
			$attachment_id = $this->upload_to_media_library($file_path);

			// Check for upload errors
			if (is_wp_error($attachment_id)) {
				error_log('CF7 File URL Replacer Error: ' . $attachment_id->get_error_message());
				continue;
			}

			// Get file details and create link
			$file_url = wp_get_attachment_url($attachment_id);
			$file_name = basename($file_path);
			$file_size = size_format(filesize($file_path));

			// Create formatted HTML link
			$file_links[] = $this->create_file_link($file_url, $file_name, $file_size);
		}

		return $file_links;
	}

	/**
	 * Upload file to WordPress Media Library
	 *
	 * @param string $file_path Path to the file.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	private function upload_to_media_library($file_path)
	{
		// Prepare file array for media_handle_sideload
		$file_array = array(
			'name' => basename($file_path),
			'tmp_name' => $file_path,
			'error' => 0,
			'size' => filesize($file_path),
		);

		// Upload to WordPress Media Library
		$attachment_id = media_handle_sideload(
			$file_array,
			0,
			null,
			array(
				'post_title' => sanitize_file_name(pathinfo($file_path, PATHINFO_FILENAME)),
			)
		);

		return $attachment_id;
	}

	/**
	 * Create formatted HTML link for file
	 *
	 * @param string $url       File URL.
	 * @param string $name      File name.
	 * @param string $size      File size.
	 * @return string Formatted HTML link.
	 */
	private function create_file_link($url, $name, $size)
	{
		return sprintf(
			'<a href="%s" style="display: inline-block; padding: 8px 12px; margin: 5px 0; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; font-weight: 500;">📎 %s (%s)</a>',
			esc_url($url),
			esc_html($name),
			esc_html($size)
		);
	}

	/**
	 * Load WordPress media dependencies
	 *
	 * @return void
	 */
	private function load_media_dependencies()
	{
		if (!function_exists('media_handle_sideload')) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
	}

	/**
	 * Add plugin action links
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links($links)
	{
		$plugin_links = array(
			'<a href="' . admin_url('admin.php?page=wpcf7') . '">' . esc_html__('Contact Forms', 'cf7-file-url-replacer') . '</a>',
		);
		return array_merge($plugin_links, $links);
	}
}
