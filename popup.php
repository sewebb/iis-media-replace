<?php
/**
 * Uploadscreen for selecting and uploading new media file
 *
 * @author      Måns Jonasson  <http://www.mansjonasson.se>
 * @copyright   Måns Jonasson 13 sep 2010
 * @version     $Revision: 2303 $ | $Date: 2010-09-13 11:12:35 +0200 (ma, 13 sep 2010) $
 * @package     WordPress
 * @subpackage  iis-media-replace
 *
 */

if ( ! current_user_can( 'upload_files' ) ) {
	wp_die( __( 'You do not have permission to upload files.', 'iis-media-replace' ) );
}

global $wpdb;

$table_name = $wpdb->prefix . 'posts';

$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = " . (int) $_GET['attachment_id'];

list($current_filename, $current_filetype) = $wpdb->get_row( $sql, ARRAY_N );

$current_filename = substr( $current_filename, ( strrpos( $current_filename, '/' ) + 1 ) );


?>
<div class="wrap">
	<h1><?php echo __( 'Replace Media Upload', 'iis-media-replace' ); ?></h1>

	<?php
	$url     = admin_url( 'upload.php?page=iis-media-replace/iis-media-replace.php&noheader=true&action=media_replace_upload&attachment_id=' . (int) $_GET['attachment_id'] );
	$action  = 'media_replace_upload';
	$formurl = wp_nonce_url( $url, $action );
	if ( FORCE_SSL_ADMIN ) {
			$formurl = str_replace( 'http:', 'https:', $formurl );
		}
	?>

	<form enctype="multipart/form-data" method="post" action="<?php echo $formurl; ?>">
	<?php
		// wp_nonce_field('iis-media-replace');
	?>
		<input type="hidden" name="ID" value="<?php echo (int) $_GET['attachment_id']; ?>" />
		<div id="message" class="updated notice notice-success is-dismissible"><p><?php printf( __( 'NOTE: You are about to replace the media file "%s". There is no undo. Think about it!', 'iis-media-replace' ), $current_filename ); ?></p></div>

		<p><?php echo __( 'Choose a file to upload from your computer', 'iis-media-replace' ); ?></p>

		<input type="file" name="userfile" />

		<?php do_action( 'emr_before_replace_type_options' ); ?>

	<?php if ( apply_filters( 'emr_display_replace_type_options', true ) ) : ?>
		<p><?php echo __( 'Select media replacement type:', 'iis-media-replace' ); ?></p>

		<label for="replace_type_1"><input CHECKED id="replace_type_1" type="radio" name="replace_type" value="replace"> <?php echo __( 'Just replace the file', 'iis-media-replace' ); ?></label>
		<p class="howto"><?php printf( __( 'Note: This option requires you to upload a file of the same type (%1$s) as the one you are replacing. The name of the attachment will stay the same (%2$s) no matter what the file you upload is called.', 'iis-media-replace' ), $current_filetype, $current_filename ); ?></p>
	<?php else : ?>
		<input type="hidden" name="replace_type" value="replace" />
	<?php endif; ?>
		<input type="submit" class="button" value="<?php echo __( 'Upload', 'iis-media-replace' ); ?>" /> <a href="#" onclick="history.back();"><?php echo __( 'Cancel', 'iis-media-replace' ); ?></a>
	</form>
</div>
