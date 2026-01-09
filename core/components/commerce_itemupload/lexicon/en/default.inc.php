<?php
/**
 * Commerce ItemUpload Lexicon
 *
 * @package commerce_itemupload
 * @subpackage lexicon
 */

$_lang['commerce_itemupload'] = 'Item Upload';
$_lang['commerce_itemupload.description'] = 'Allows customers to upload files that are attached to order items and included in order emails.';

// Configuration fields
$_lang['commerce_itemupload.upload_fields'] = 'Upload Field Names';
$_lang['commerce_itemupload.upload_fields.description'] = 'Comma-separated list of field names that can be used for uploads (e.g., "upload,file,custom_file").';

$_lang['commerce_itemupload.allowed_extensions'] = 'Allowed File Extensions';
$_lang['commerce_itemupload.allowed_extensions.description'] = 'Comma-separated list of allowed file extensions (e.g., "jpg,jpeg,png,gif,pdf,doc,docx").';

$_lang['commerce_itemupload.max_file_size'] = 'Maximum File Size';
$_lang['commerce_itemupload.max_file_size.description'] = 'Maximum file size in bytes (default: 5242880 = 5MB).';

$_lang['commerce_itemupload.media_source'] = 'Media Source';
$_lang['commerce_itemupload.media_source.description'] = 'Select the media source where uploads should be stored. This is required.';

$_lang['commerce_itemupload.upload_path'] = 'Upload Path';
$_lang['commerce_itemupload.upload_path.description'] = 'Relative path within the media source/base path where uploads should be stored (e.g., "uploads/commerce/").';

$_lang['commerce_itemupload.message_keys'] = 'Message Keys';
$_lang['commerce_itemupload.message_keys.description'] = 'Comma-separated list of message keys for which uploaded files should be attached to emails (e.g., "order_confirmation,order_completed"). Leave empty to attach to all emails.';

$_lang['commerce_itemupload.allowed_product_ids'] = 'Allowed Product IDs';
$_lang['commerce_itemupload.allowed_product_ids.description'] = 'Comma-separated list of product IDs for which email attachments should be added. Leave empty to allow attachments for all products.';

// Dashboard display
$_lang['commerce_itemupload.uploaded_files'] = 'Uploaded Files';
$_lang['commerce_itemupload.field.upload'] = 'Upload';

// Error messages
$_lang['commerce_itemupload.error.file_size_exceeded'] = 'File size exceeds maximum allowed size of [[+max_size]]MB';
$_lang['commerce_itemupload.error.file_type_not_allowed'] = 'File type not allowed. Allowed types: [[+allowed_types]]';
$_lang['commerce_itemupload.error.no_media_source'] = 'Media source is not configured. Please configure a media source in the module settings.';
$_lang['commerce_itemupload.error.failed_to_read_file'] = 'Failed to read uploaded file';
$_lang['commerce_itemupload.error.failed_to_save_file'] = 'Failed to save uploaded file';
$_lang['commerce_itemupload.error.upload_failed'] = 'Upload failed for field [[+field]]: [[+error]]';
