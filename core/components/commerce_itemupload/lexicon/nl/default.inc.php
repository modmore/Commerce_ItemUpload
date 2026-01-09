<?php
/**
 * Commerce ItemUpload Lexicon (Dutch)
 *
 * @package commerce_itemupload
 * @subpackage lexicon
 */

$_lang['commerce_itemupload'] = 'Item Upload';
$_lang['commerce_itemupload.description'] = 'Stelt klanten in staat om bestanden te uploaden die aan order items worden toegevoegd en worden meegestuurd in order emails.';

// Configuration fields
$_lang['commerce_itemupload.upload_fields'] = 'Upload Veldnamen';
$_lang['commerce_itemupload.upload_fields.description'] = 'Komma-gescheiden lijst van veldnamen die gebruikt kunnen worden voor uploads (bijv. "upload,file,custom_file").';

$_lang['commerce_itemupload.allowed_extensions'] = 'Toegestane Bestandsextensies';
$_lang['commerce_itemupload.allowed_extensions.description'] = 'Komma-gescheiden lijst van toegestane bestandsextensies (bijv. "jpg,jpeg,png,gif,pdf,doc,docx").';

$_lang['commerce_itemupload.max_file_size'] = 'Maximale Bestandsgrootte';
$_lang['commerce_itemupload.max_file_size.description'] = 'Maximale bestandsgrootte in bytes (standaard: 5242880 = 5MB).';

$_lang['commerce_itemupload.media_source'] = 'Media Source';
$_lang['commerce_itemupload.media_source.description'] = 'Selecteer de media source waar uploads moeten worden opgeslagen. Dit is verplicht.';

$_lang['commerce_itemupload.upload_path'] = 'Upload Pad';
$_lang['commerce_itemupload.upload_path.description'] = 'Relatief pad binnen de media source/base path waar uploads moeten worden opgeslagen (bijv. "uploads/commerce/").';

$_lang['commerce_itemupload.message_keys'] = 'Bericht Keys';
$_lang['commerce_itemupload.message_keys.description'] = 'Komma-gescheiden lijst van bericht keys waarvoor geüploade bestanden moeten worden toegevoegd aan emails (bijv. "order_confirmation,order_completed"). Laat leeg om aan alle emails toe te voegen.';

$_lang['commerce_itemupload.allowed_product_ids'] = 'Toegestane Product IDs';
$_lang['commerce_itemupload.allowed_product_ids.description'] = 'Komma-gescheiden lijst van product IDs waarvoor email bijlagen moeten worden toegevoegd. Laat leeg om bijlagen voor alle producten toe te staan.';

// Dashboard weergave
$_lang['commerce_itemupload.uploaded_files'] = 'Geüploade Bestanden';
$_lang['commerce_itemupload.field.upload'] = 'Upload';

// Foutmeldingen
$_lang['commerce_itemupload.error.file_size_exceeded'] = 'Bestandsgrootte overschrijdt de maximaal toegestane grootte van [[+max_size]]MB';
$_lang['commerce_itemupload.error.file_type_not_allowed'] = 'Bestandstype niet toegestaan. Toegestane types: [[+allowed_types]]';
$_lang['commerce_itemupload.error.no_media_source'] = 'Media source is niet geconfigureerd. Configureer een media source in de module instellingen.';
$_lang['commerce_itemupload.error.failed_to_read_file'] = 'Kon geüpload bestand niet lezen';
$_lang['commerce_itemupload.error.failed_to_save_file'] = 'Kon geüpload bestand niet opslaan';
$_lang['commerce_itemupload.error.upload_failed'] = 'Upload mislukt voor veld [[+field]]: [[+error]]';
