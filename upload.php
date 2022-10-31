<?php
if ( ! current_user_can( 'upload_files' ) ) {
	wp_die( __( 'You do not have permission to upload files.', 'enable-media-replace' ) );
}

// Define DB table names
global $wpdb;
$table_name          = $wpdb->prefix . 'posts';
$postmeta_table_name = $wpdb->prefix . 'postmeta';

/**
 * Delete a media file and its thumbnails.
 *
 * @param string     $current_file
 * @param array|null $metadta
 */
function emr_delete_current_files( $current_file, $metadata = null ) {
	// Delete old file

	// Find path of current file
	$current_path = substr( $current_file, 0, ( strrpos( $current_file, '/' ) ) );

	// Check if old file exists first
	if ( file_exists( $current_file ) ) {
		// Now check for correct file permissions for old file
		clearstatcache();
		if ( is_writable( dirname( $current_file ) ) ) {
			// Everything OK; delete the file
			unlink( $current_file );
		} else {
			// File exists, but has wrong permissions. Let the user know.
			printf( __( 'The file %1$s can not be deleted by the web server, most likely because the permissions on the file are wrong.', 'enable-media-replace' ), $current_file );
			exit;
		}
	}

	// Delete old resized versions if this was an image
	$suffix = substr( $current_file, ( strlen( $current_file ) - 4 ) );
	$prefix = substr( $current_file, 0, ( strlen( $current_file ) - 4 ) );
	$imgAr  = array( '.png', '.gif', '.jpg' );
	if ( in_array( $suffix, $imgAr ) ) {
		// It's a png/gif/jpg based on file name
		// Get thumbnail filenames from metadata
		if ( empty( $metadata ) ) {
			$metadata = wp_get_attachment_metadata( $_POST['ID'] );
		}

		if ( is_array( $metadata ) ) { // Added fix for error messages when there is no metadata (but WHY would there not be? I don't know…)
			foreach ( $metadata['sizes'] as $thissize ) {
				// Get all filenames and do an unlink() on each one;
				$thisfile = $thissize['file'];
				// Create array with all old sizes for replacing in posts later
				$oldfilesAr[] = $thisfile;
				// Look for files and delete them
				if ( strlen( $thisfile ) ) {
					$thisfile = $current_path . '/' . $thissize['file'];
					if ( file_exists( $thisfile ) ) {
						unlink( $thisfile );
					}
				}
			}
		}
	}

}

// Get old guid and filetype from DB
$sql                                       = "SELECT guid, post_mime_type FROM $table_name WHERE ID = '" . (int) $_POST['ID'] . "'";
list($current_filename, $current_filetype) = $wpdb->get_row( $sql, ARRAY_N );

// Massage a bunch of vars
$current_guid     = $current_filename;
$current_filename = substr( $current_filename, ( strrpos( $current_filename, '/' ) + 1 ) );

$current_file     = get_attached_file( (int) $_POST['ID'], apply_filters( 'emr_unfiltered_get_attached_file', true ) );
$current_path     = substr( $current_file, 0, ( strrpos( $current_file, '/' ) ) );
$current_file     = str_replace( '//', '/', $current_file );
$current_filename = basename( $current_file );
$current_metadata = wp_get_attachment_metadata( $_POST['ID'] );

$replace_type = $_POST['replace_type'];

if ( is_uploaded_file( $_FILES['userfile']['tmp_name'] ) ) {

	// New method for validating that the uploaded file is allowed, using WP:s internal wp_check_filetype_and_ext() function.
	$filedata = wp_check_filetype_and_ext( $_FILES['userfile']['tmp_name'], $_FILES['userfile']['name'] );

	if ( $filedata['ext'] == '' ) {
		echo __( 'File type does not meet security guidelines. Try another.', 'enable-media-replace' );
		exit;
	}

	$new_filename = $_FILES['userfile']['name'];
	$new_filesize = $_FILES['userfile']['size'];
	$new_filetype = $filedata['type'];

	// save original file permissions
	$original_file_perms = fileperms( $current_file ) & 0777;

	if ( $replace_type == 'replace' ) {
		// Drop-in replace and we don't even care if you uploaded something that is the wrong file-type.
		// That's your own fault, because we warned you!

		emr_delete_current_files( $current_file, $current_metadata );

		// Move new file to old location/name
		move_uploaded_file( $_FILES['userfile']['tmp_name'], $current_file );

		// Chmod new file to original file permissions
		@chmod( $current_file, $original_file_perms );

		// Make thumb and/or update metadata
		wp_update_attachment_metadata( (int) $_POST['ID'], wp_generate_attachment_metadata( (int) $_POST['ID'], $current_file ) );

		// Trigger possible updates on CDN and other plugins
		update_attached_file( (int) $_POST['ID'], $current_file );
	}

	// echo "Updated: " . $number_of_updates;

	$returnurl = admin_url( "/post.php?post={$_POST["ID"]}&action=edit&message=1" );

	// Execute hook actions - thanks rubious for the suggestion!
	if ( isset( $new_guid ) ) {
do_action( 'enable-media-replace-upload-done', $new_guid, $current_guid ); }
} else {
	//TODO Better error handling when no file is selected.
	//For now just go back to media management
	$returnurl = admin_url( 'upload.php' );
}

if ( FORCE_SSL_ADMIN ) {
	$returnurl = str_replace( 'http:', 'https:', $returnurl );
}

// Allow developers to override $returnurl
$returnurl = apply_filters( 'emr_returnurl', $returnurl );

//save redirection
wp_redirect( $returnurl );

