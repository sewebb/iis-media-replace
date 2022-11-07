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

		<p>
			<input type="submit" class="button" value="<?php echo __( 'Upload', 'iis-media-replace' ); ?>" /> <a href="#" onclick="history.back();"><?php echo __( 'Cancel', 'iis-media-replace' ); ?></a>
		</p>
	</form>
</div>
