<?php
/** Check the mime type by "fileinfo" function.
** WordPress checks the mime type using "fileinfo" function when a file is uploaded to WordPress site in the media admin menu because of the security reason. The tool outputs the mime type by "fileinfo" function.
**/

if ($argc !== 2):
	echo "Usage: ".$argv[0]." file_path or url_path.\n";
	exit;
endif;

$file = $argv[1];
$file_data = file_get_contents($file);
$finfo = finfo_open( FILEINFO_MIME_TYPE );
$real_mime = finfo_buffer( $finfo, $file_data );
finfo_close( $finfo );

echo "mime type for fileinfo function: [" . $real_mime . "]\n";

?>