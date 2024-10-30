<?php
/*
Plugin Name:   mi13-errors-log
Plugin URI:    https://wordpress.org/plugins/mi13-errors-log
Description:   Plugin for monitoring errors on your website through the admin panel.
Author:        mi13
version:       1.1
License:       GPL v2 or later
License URI:   https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function mi13_errors_log_menu() {
	$i = 0;
	if( wp_validate_boolean(WP_DEBUG_LOG) && wp_validate_boolean(WP_DEBUG) ) {
		if( WP_DEBUG_LOG === true ) {
			$logfile = WP_CONTENT_DIR . '/debug.log';
		} else {
			$logfile = WP_DEBUG_LOG;
		}
		$logfile = wp_normalize_path($logfile);
		global $wp_filesystem;
		if( !$wp_filesystem ){
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		} 
		if( isset( $_POST['mi13_errors_clear'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mi13_errors_clear'] ) ), 'mi13_errors_log') ){
			if( $wp_filesystem->is_writable( $logfile ) && $wp_filesystem->put_contents( $logfile, '' ) ){
				wp_admin_notice('All errors was deleted!',['type' => 'success']);
			} else {
				wp_admin_notice('Error file writing (' . $logfile . ')!',['type' => 'error']);
			}
		} elseif( $wp_filesystem->is_readable( $logfile ) ){
			$lines = $wp_filesystem->get_contents_array( $logfile );
			if($lines) {
				foreach($lines as $line) {
					if( $line[0] === '[' ) $i++;
				}
			}
		} else {
			wp_admin_notice('Error file reading (' . $logfile . ')!',['type' => 'error']);
		}
	} else {
		wp_admin_notice('Please enable WP_DEBUG and WP_DEBUG_LOG in wp-config file!',['type' => 'warning']);
	}
	$notification_count = $i;
	$menu = $notification_count ? sprintf( 'mi13 errors <span class="awaiting-mod">%d</span>', $notification_count ) : 'mi13 errors';
	add_menu_page( 'mi13 errors log', $menu, 'manage_options', 'mi13_errors_log', 'mi13_errors_log_page', 'dashicons-code-standards', 3 );
}
add_action('admin_menu', 'mi13_errors_log_menu');

function mi13_errors_log_page() {
?>
<div class="wrap">
	<h2><?php echo esc_html(get_admin_page_title()); ?></h2>
	<div style="background-color:#fff;padding:16px">
	<?php
	if( WP_DEBUG_LOG === true ) {
		$logfile = WP_CONTENT_DIR . '/debug.log';
	}  else {
		$logfile = WP_DEBUG_LOG;
	}
	$logfile = wp_normalize_path($logfile);
	$count = 0;
	$lines = array();
	echo '<p>Debug file path: ' . esc_html( $logfile ) . '</p>';
	global $wp_filesystem;
	if( !$wp_filesystem ){
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}
	
	if( $wp_filesystem->is_readable( $logfile ) ) {
		$lines = $wp_filesystem->get_contents_array( $logfile );
		$count = count($lines);
		if( $count ) {
			$return = '';
			$trace = false;
			$trace_arr = array();
			$unicue = array();
			$i = 0;
			$b = 0;
			$reversed = array_reverse($lines);
			$return .= '<h3>Last errors..</h3>';
			foreach($reversed as $line) {
				$cropping = false;
				if( $b > 9 ) break;
				if( $line[0] === '[' ) {
					$val = explode(']',$line);
					if( strlen($val[1]) > 1000 ) {
						$val[1] = substr( $val[1], 0, 1000 ) . '..';
						$cropping = true;
					}
					if( in_array($val[1], $unicue) ){
						$trace_arr = array();
						$trace = false;
						$i++;
						continue;
					} else $unicue[] = $val[1];
					$i++;
					$b++;
					$val[1] = str_replace( 'PHP Fatal error', '<span style="color:red">PHP Fatal error</span>', $val[1] );
					if( !$cropping ) {
						$pos = strrpos($val[1], ' in ');
						if( $pos !== false ) {
							$val[1] = substr_replace($val[1], '<br>in ', $pos, 4);
						}
					}
					$return .= '<p><b>' . $b . '</b> : <span style="color:#686868">' . $val[0] . ']</span> ' . $val[1] . '</p>';
					
					if( $trace ){
						$trace = false;
						$return .= '<p style="margin-left:40px;background-color:#686868;color:#fff;padding:8px;font-size:10px">';
						while( count($trace_arr) > 0 ) {
							$return .= array_pop($trace_arr) . '<br>';
						}
						$return .= '</p>';
						
					}
				} else {
					$trace = true;
					$trace_arr[] = $line;
				}
			}
			if( $i > $b ) {
				$c = $i - $b;
				$return .= '<p>' . $c . ' duplicates were hidden!</p>';
			}
			$nonce = wp_create_nonce( 'mi13_errors_log' );
			echo wp_kses( $return, 'post' );
		?>
		</div>
		<form method="post">
			<input type="hidden" name="mi13_errors_clear" value="<?php echo esc_attr($nonce); ?>" />			
			<p><input type="submit" class="button button-primary" value="clear log" /></p>		 
		</form>
		<?php
		} else echo '<p style="color:green">Not errors found.</p>';
	} else echo '<p style="color:red">File read error.</p>';
	?>
	</div>
	<?php
}