# Commerce ItemUpload

A Commerce module that allows customers to upload files that are attached to order items and included in order emails.

## Features

- Secure file upload handling with validation
- Configurable upload field names, file types, and maximum file size
- Support for MODX Media Sources
- Automatic attachment of uploaded files to order emails based on message keys
- Security measures to prevent path traversal attacks

## Installation

1. Copy the `Commerce_ItemUpload` directory to your MODX root
2. Run the bootstrap script: `Commerce_ItemUpload/_bootstrap/index.php`
3. Go to Commerce > Modules in the MODX manager
4. Enable the "Item Upload" module
5. Configure the module settings

## Configuration

The module can be configured with the following settings:

- **Upload Field Names**: Comma-separated list of field names that can be used for uploads (e.g., "upload,file,custom_file")
- **Allowed File Extensions**: Comma-separated list of allowed file extensions (e.g., "jpg,jpeg,png,gif,pdf,doc,docx")
- **Maximum File Size**: Maximum file size in bytes (default: 5242880 = 5MB)
- **Media Source**: Select the media source where uploads should be stored (optional)
- **Upload Path**: Relative path within the media source/base path where uploads should be stored (e.g., "uploads/commerce/")
- **Message Keys**: Comma-separated list of message keys for which uploaded files should be attached to emails (e.g., "order_confirmation,order_completed"). Leave empty to attach to all emails.

## Usage

### Adding Upload to Cart

The module automatically handles file uploads when items are added to the cart. Simply include file upload fields in your add-to-cart form:

```html
<form method="post" enctype="multipart/form-data" action="[[~cart]]">
    <input type="hidden" name="add_to_cart" value="1" />
    <input type="hidden" name="product" value="[[*id]]" />
    <input type="file" name="upload" />
    <button type="submit">Add to Cart</button>
</form>
```

The module will automatically:
1. Listen to the `EVENT_ITEM_ADDED_TO_CART` event
2. Process any uploaded files that match your module configuration
3. Validate file type, size, and security
4. Upload the file to the configured media source and path
5. Store the file path in the item properties

**Note:** The field name(s) used in your form must match the configured "Upload Field Names" in the module settings (default: "upload").

### Email Attachments

The module automatically attaches uploaded files to order emails based on the configured message keys. Files are attached when:
- The order email has a message_key that matches one of the configured message keys (or all emails if no keys are configured)
- The uploaded file path is valid and the file exists

## Security

The module includes several security measures:

- Path traversal protection (prevents `../` attacks)
- File extension validation
- File size limits
- Secure filename generation
- Path validation to ensure files are within the configured upload directory

## License

MIT
