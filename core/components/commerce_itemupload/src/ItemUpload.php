<?php

namespace modmore\Commerce_ItemUpload;

use modmore\Commerce\Admin\Widgets\Form\NumberField;
use modmore\Commerce\Admin\Widgets\Form\SelectField;
use modmore\Commerce\Admin\Widgets\Form\TextField;
use modmore\Commerce\Admin\Widgets\Form\Validation\Required;
use modmore\Commerce\Events\Admin\OrderItemDetail;
use modmore\Commerce\Events\Cart\Item;
use modmore\Commerce\Events\Mail;
use modmore\Commerce\Modules\BaseModule;
use modmore\Commerce\Dispatcher\EventDispatcher;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class ItemUpload extends BaseModule
{
    /** @var \modMediaSource|null Cached media source instance */
    protected $mediaSource = null;

    /** @var string|null Cached base path from media source */
    protected $mediaSourceBasePath = null;

    public function getName()
    {
        $this->adapter->loadLexicon('commerce_itemupload:default');
        return $this->adapter->lexicon('commerce_itemupload');
    }

    public function getAuthor()
    {
        return 'modmore';
    }

    public function getDescription()
    {
        return $this->adapter->lexicon('commerce_itemupload.description');
    }

    public function initialize(EventDispatcher $dispatcher)
    {
        // Load lexicon
        $this->adapter->loadLexicon('commerce_itemupload:default');

        // Listen to cart events to handle uploads
        $dispatcher->addListener(\Commerce::EVENT_ITEM_ADDED_TO_CART, [$this, 'handleItemAddedToCart']);

        // Listen to mail events to attach files
        $dispatcher->addListener(\Commerce::EVENT_SEND_MAIL, [$this, 'handleSendMail']);

        // Listen to dashboard order item detail to show uploaded files
        $dispatcher->addListener(\Commerce::EVENT_DASHBOARD_ORDER_ITEM_DETAIL, [$this, 'showUploadsOnDetailRow']);
    }

    /**
     * Handles the EVENT_ITEM_ADDED_TO_CART event to process uploads
     * Directly processes $_FILES to have full control over what is accepted
     *
     * @param Item $event
     */
    public function handleItemAddedToCart(Item $event)
    {
        $item = $event->getItem();

        // Get configured upload field names
        $uploadFields = $this->getUploadFieldNames();

        // Process each configured upload field
        foreach ($uploadFields as $fieldName) {
            // Check if a file was uploaded for this field
            if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $file = $_FILES[$fieldName];

            // Process the upload
            $result = $this->processUpload($fieldName, $file);

            if ($result['success']) {
                // Store the file path in item properties
                $item->setProperty('upload_' . $fieldName, $result['path']);
                $item->setProperty('upload_' . $fieldName . '_full', $this->getFileUrl($result['path']));
                $item->save();
            } else {
                // Log the error
                $this->adapter->loadLexicon('commerce_itemupload:default');
                $this->adapter->log(
                    \modX::LOG_LEVEL_ERROR,
                    $this->adapter->lexicon('commerce_itemupload.error.upload_failed', [
                        'field' => $fieldName,
                        'error' => $result['error']
                    ])
                );
            }
        }
    }

    /**
     * Handles the EVENT_SEND_MAIL event to attach uploaded files
     *
     * @param Mail $event
     */
    public function handleSendMail(Mail $event)
    {
        $order = $event->getOrder();
        $message = $event->getMessage();

        // Get configured message keys that should have attachments
        $messageKeys = $this->getMessageKeys();

        // If message keys are configured, check if this message matches
        if (!empty($messageKeys)) {
            $messageKey = $message->get('message_key');
            if (empty($messageKey) || !in_array($messageKey, $messageKeys, true)) {
                return; // Skip if message key doesn't match or is empty
            }
        }
        // If message keys is empty, allow attachments for all emails

        // Get all items from the order
        $items = $order->getItems();
        $uploadFields = $this->getUploadFieldNames();

        // Get allowed product IDs (if configured)
        $allowedProductIds = $this->getAllowedProductIds();

        foreach ($items as $item) {
            foreach ($uploadFields as $fieldName) {
                $uploadPath = $item->getProperty('upload_' . $fieldName);

                if (!empty($uploadPath)) {
                    // Validate path again for security
                    if ($this->validateUploadPath($uploadPath)) {
                        $fullPath = $this->getFullUploadPath($uploadPath);

                        // Check if file exists
                        if (file_exists($fullPath) && is_readable($fullPath)) {
                            $event->attach($fullPath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Shows uploaded files in the order item detail row in the dashboard
     *
     * @param OrderItemDetail $event
     */
    public function showUploadsOnDetailRow(OrderItemDetail $event)
    {
        $item = $event->getItem();
        $uploadFields = $this->getUploadFieldNames();
        $uploads = [];

        foreach ($uploadFields as $fieldName) {
            $uploadPath = $item->getProperty('upload_' . $fieldName);

            if (!empty($uploadPath)) {
                // Validate the path for security
                if (!$this->validateUploadPath($uploadPath)) {
                    continue;
                }

                // Get file URL from media source
                $fileUrl = $this->getFileUrl($uploadPath);
                if ($fileUrl) {
                    $uploads[] = [
                        'field' => $fieldName,
                        'path' => $uploadPath,
                        'url' => $fileUrl,
                        'filename' => basename($uploadPath)
                    ];
                }
            }
        }

        if (!empty($uploads)) {
            $this->adapter->loadLexicon('commerce_itemupload:default');
            $output = '<div class="commerce-itemupload-uploads"><h4>' . $this->adapter->lexicon('commerce_itemupload.uploaded_files') . '</h4><ul>';

            foreach ($uploads as $upload) {
                $fieldLabel = $upload['field'];
                if ($this->adapter->lexicon('commerce_itemupload.field.' . $upload['field']) !== 'commerce_itemupload.field.' . $upload['field']) {
                    $fieldLabel = $this->adapter->lexicon('commerce_itemupload.field.' . $upload['field']);
                } else {
                    $fieldLabel = str_replace(['-','_'], ' ', ucfirst($fieldLabel));
                }

                $output .= '<li>';
                $output .= '<strong>' . htmlspecialchars($fieldLabel) . ':</strong> ';
                $output .= '<a href="' . htmlspecialchars($upload['url']) . '" target="_blank" rel="noopener">';
                $output .= htmlspecialchars($upload['filename']);
                $output .= '</a>';
                $output .= '</li>';
            }

            $output .= '</ul></div>';
            $event->addRow($output);
        }
    }

    /**
     * Gets the URL for a file in the media source
     *
     * @param string $relativePath
     * @return string|null
     */
    protected function getFileUrl($relativePath)
    {
        $source = $this->getMediaSource();
        if (!$source) {
            return null;
        }

        $uploadPath = $this->getConfig('upload_path', 'uploads/commerce/');
        $uploadPath = rtrim($uploadPath, '/') . '/';
        $fullPath = $uploadPath . $relativePath;

        // Get the object URL from media source
        $url = $source->getObjectUrl($fullPath);
        if (empty($url)) {
            // Fallback: construct URL from base URL if available
            $baseUrl = $source->getBaseUrl();
            if (!empty($baseUrl)) {
                $url = rtrim($baseUrl, '/') . '/' . ltrim($fullPath, '/');
            }
        }

        return $url;
    }

    /**
     * Validates an upload path to prevent path traversal attacks
     *
     * @param string $path
     * @return bool
     */
    protected function validateUploadPath($path)
    {
        // Reject empty paths
        if (empty($path)) {
            return false;
        }

        // Reject paths with directory traversal attempts
        if (strpos($path, '..') !== false) {
            return false;
        }

        // Reject absolute paths (should be relative to upload directory)
        if (strpos($path, '/') === 0 || preg_match('/^[a-zA-Z]:\\\\/', $path)) {
            return false;
        }

        // Get configured upload path
        $uploadPath = $this->getConfig('upload_path', 'uploads/commerce/');
        $uploadPath = rtrim($uploadPath, '/') . '/';

        // Ensure the path is within the configured upload directory
        $fullPath = $this->getFullUploadPath($path);
        $basePath = $this->getFullUploadPath('');

        // Normalize paths for comparison
        $fullPath = realpath($fullPath);
        $basePath = realpath($basePath);

        if ($fullPath === false || $basePath === false) {
            return false;
        }

        // Check that the resolved path is within the base path
        return strpos($fullPath, $basePath) === 0;
    }

    /**
     * Gets the cached media source instance
     *
     * @return \modMediaSource|null
     */
    protected function getMediaSource()
    {
        // Return cached instance if available (check for both null and false to handle "not found" case)
        if ($this->mediaSource !== null && $this->mediaSource !== false) {
            return $this->mediaSource;
        }

        // If we've already checked and found nothing, return null
        if ($this->mediaSource === false) {
            return null;
        }

        // Get media source if configured
        $mediaSourceId = $this->getConfig('media_source');
        if (!empty($mediaSourceId)) {
            /** @var \modMediaSource $source */
            $source = $this->adapter->getObject('modMediaSource', $mediaSourceId);
            if ($source) {
                $source->initialize();
                $this->mediaSource = $source;
                $this->mediaSourceBasePath = $source->getBasePath();
                return $source;
            }
        }

        // Cache false to avoid repeated lookups when no media source is configured
        $this->mediaSource = false;
        return null;
    }

    /**
     * Gets the full absolute path for an upload
     * Requires media source to be configured
     *
     * @param string $relativePath
     * @return string
     */
    protected function getFullUploadPath($relativePath = '')
    {
        $uploadPath = $this->getConfig('upload_path', 'uploads/commerce/');
        $uploadPath = rtrim($uploadPath, '/') . '/';

        // Get cached media source base path (required)
        $source = $this->getMediaSource();
        if (!$source || empty($this->mediaSourceBasePath)) {
            // This should not happen if module is configured correctly
            throw new \RuntimeException('Media source is required but not configured');
        }

        return rtrim($this->mediaSourceBasePath, '/') . '/' . ltrim($uploadPath, '/') . ltrim($relativePath, '/');
    }

    /**
     * Gets configured upload field names
     *
     * @return array
     */
    protected function getUploadFieldNames()
    {
        $fields = $this->getConfig('upload_fields', 'upload');
        return array_map('trim', explode(',', $fields));
    }

    /**
     * Gets configured message keys that should have attachments
     *
     * @return array
     */
    protected function getMessageKeys()
    {
        $keys = $this->getConfig('message_keys', '');
        if (empty($keys)) {
            return [];
        }
        return array_map('trim', explode(',', $keys));
    }

    /**
     * Gets allowed file extensions
     *
     * @return array
     */
    protected function getAllowedExtensions()
    {
        $extensions = $this->getConfig('allowed_extensions', 'jpg,jpeg,png,gif,pdf,doc,docx');
        return array_map('trim', explode(',', strtolower($extensions)));
    }

    /**
     * Gets allowed product IDs for email attachments
     *
     * @return array Empty array means all products are allowed
     */
    protected function getAllowedProductIds()
    {
        $productIds = $this->getConfig('allowed_product_ids', '');
        if (empty($productIds)) {
            return [];
        }
        return array_map('intval', array_filter(array_map('trim', explode(',', $productIds)), function($id) {
            return $id > 0;
        }));
    }

    /**
     * Processes a file upload and returns the relative path
     * This is called directly from handleItemAddedToCart when $_FILES is detected
     *
     * @param string $fieldName The name of the upload field
     * @param array $file The $_FILES array entry for this field
     * @return array Returns array with success status and data
     */
    protected function processUpload($fieldName, $file)
    {
        // Ensure lexicon is loaded
        $this->adapter->loadLexicon('commerce_itemupload:default');

        // Get configuration values
        $allowedExtensions = $this->getAllowedExtensions();
        $maxFileSize = $this->getConfig('max_file_size', 5242880);

        // Validate file size
        if ($file['size'] > $maxFileSize) {
            $maxSizeMB = round($maxFileSize / 1024 / 1024, 2);
            return [
                'success' => false,
                'error' => $this->adapter->lexicon('commerce_itemupload.error.file_size_exceeded', [
                    'max_size' => $maxSizeMB
                ])
            ];
        }

        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            return [
                'success' => false,
                'error' => $this->adapter->lexicon('commerce_itemupload.error.file_type_not_allowed', [
                    'allowed_types' => implode(', ', $allowedExtensions)
                ])
            ];
        }

        // Generate secure filename
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $filename = $sanitizedFilename . '_' . time() . '_' . uniqid() . '.' . $extension;

        // Get upload directory path (relative path within media source)
        $uploadPath = $this->getConfig('upload_path', 'uploads/commerce/');
        $uploadPath = rtrim($uploadPath, '/') . '/';

        // Media source is required
        $source = $this->getMediaSource();
        if (!$source) {
            return [
                'success' => false,
                'error' => $this->adapter->lexicon('commerce_itemupload.error.no_media_source')
            ];
        }

        // Read file content from temporary upload
        $fileContent = file_get_contents($file['tmp_name']);
        if ($fileContent === false) {
            return [
                'success' => false,
                'error' => $this->adapter->lexicon('commerce_itemupload.error.failed_to_read_file')
            ];
        }

        // Create object in media source
        $success = $source->createObject($uploadPath, $filename, $fileContent);
        if (!$success) {
            return [
                'success' => false,
                'error' => $this->adapter->lexicon('commerce_itemupload.error.failed_to_save_file')
            ];
        }

        // Return relative path (relative to upload_path)
        $relativePath = $filename;

        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size']
        ];
    }

    public function getModuleConfiguration(\comModule $module)
    {
        $fields = [];

        // Upload field names
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[upload_fields]',
            'label' => $this->adapter->lexicon('commerce_itemupload.upload_fields'),
            'description' => $this->adapter->lexicon('commerce_itemupload.upload_fields.description'),
            'value' => $module->getProperty('upload_fields', 'upload')
        ]);

        // Allowed file extensions
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[allowed_extensions]',
            'label' => $this->adapter->lexicon('commerce_itemupload.allowed_extensions'),
            'description' => $this->adapter->lexicon('commerce_itemupload.allowed_extensions.description'),
            'value' => $module->getProperty('allowed_extensions', 'jpg,jpeg,png,gif,pdf,doc,docx')
        ]);

        // Max file size (in bytes)
        $fields[] = new NumberField($this->commerce, [
            'name' => 'properties[max_file_size]',
            'label' => $this->adapter->lexicon('commerce_itemupload.max_file_size'),
            'description' => $this->adapter->lexicon('commerce_itemupload.max_file_size.description'),
            'value' => $module->getProperty('max_file_size', 5242880) // 5MB default
        ]);

        // Media source (required)
        $mediaSources = [];
        $sources = $this->adapter->getIterator('modMediaSource');
        foreach ($sources as $source) {
            $mediaSources[] = [
                'value' => $source->get('id'),
                'label' => $source->get('name') . ' (' . $source->get('id') . ')'
            ];
        }

        $mediaSourceField = new SelectField($this->commerce, [
            'name' => 'properties[media_source]',
            'label' => $this->adapter->lexicon('commerce_itemupload.media_source'),
            'description' => $this->adapter->lexicon('commerce_itemupload.media_source.description'),
            'value' => $module->getProperty('media_source', ''),
            'options' => $mediaSources,
            'validation' => [
                new Required($this->commerce, [
                    'message' => $this->adapter->lexicon('commerce_itemupload.error.no_media_source')
                ])
            ]
        ]);
        $fields[] = $mediaSourceField;

        // Upload path
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[upload_path]',
            'label' => $this->adapter->lexicon('commerce_itemupload.upload_path'),
            'description' => $this->adapter->lexicon('commerce_itemupload.upload_path.description'),
            'value' => $module->getProperty('upload_path', 'uploads/commerce/')
        ]);

        // Message keys
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[message_keys]',
            'label' => $this->adapter->lexicon('commerce_itemupload.message_keys'),
            'description' => $this->adapter->lexicon('commerce_itemupload.message_keys.description'),
            'value' => $module->getProperty('message_keys', '')
        ]);

        // Allowed product IDs
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[allowed_product_ids]',
            'label' => $this->adapter->lexicon('commerce_itemupload.allowed_product_ids'),
            'description' => $this->adapter->lexicon('commerce_itemupload.allowed_product_ids.description'),
            'value' => $module->getProperty('allowed_product_ids', '')
        ]);

        return $fields;
    }
}

