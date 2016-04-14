<?php
/*
 * Plugin Name: e9-utils
 * Plugin URI: URI: https://github.com/je9/e9-utils
 * GitHub Plugin URI: je9/e9-utils
 * Description: E9 tools and improvements for WordPress.
 * Version: 0.12
 * Author: justin@e9.nz
 * Author URI: http://e9.nz
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * RequiresWP: 4.5
 * RequiresPHP: 5.3.0
*/

function e9_utils_dashboard_widget_function() {
  global $wpdb;
  $query = "SELECT post_modified
    FROM $wpdb->posts
    WHERE post_title <> 'Auto Draft'
    ORDER BY post_date DESC
    LIMIT 1;
  ";

  $site_mod_date = $wpdb->get_var( $query );
  $site_mod_weekday = date( 'w', strtotime( $site_mod_date ) );

  $current_date = date( 'Y-m-d H:i:s' );

  $backup_days = [
    'sunday last',
    'tuesday this',
    'thursday this',
    'friday this',
    'saturday this' ];

  function get_date_from_desc( $day_desc ) {
    return date(
      "Y-m-d H:i:s",
      strtotime( $day_desc . ' week' ) + 60 * 60 * 12 );
  }

  $last_backup_date = get_date_from_desc( $backup_days[ 0 ] );

  foreach ( $backup_days as $day ) {
    $day_bu_time = get_date_from_desc( $day );
    if ( strtotime( $current_date ) > strtotime( $day_bu_time ) ) :
      $last_backup_date = $day_bu_time;
    else :
      break;
    endif;
  }

  $time_difference = strtotime( $last_backup_date ) - strtotime( $site_mod_date );
  $time_difference = floor( $time_difference / 60 / 60 );
  // backups occur between 6am and midday. So we need a 6 hr gap to ensure that
  // the backup was done after the edit. This also flags sites that have been
  // edited within the last few hours: edits might still be happening.
  $time_message = '<span style="color: #c11; font-weight: 700;">
    Backup is too old</span>';
  if ( $time_difference >= 0 ) :
    $time_message = '<span style="color: #c81; font-weight: 700;">
      Backup close to edit. Check time on S3.</span>';
    if ( $time_difference >= 6 ) :
      $time_message = '<span style="color: #1c8; font-weight: 700;">
        Backup OK</span>';
    endif;
  endif;

?>
<style>
  dd {
    font-weight: bold;
  }
</style>
<dl>
  <dt>Last content edit: </dt>
  <dd><?php echo $site_mod_date; ?></dd>
  <dt>Last S3 backup: </dt>
  <dd><?php echo $last_backup_date; ?></dd>
  <dt>Backup status: </dt>
  <dd><?php echo $time_message; ?></dd>
</dl>
<?php
}

function e9_utils_add_dashboard_widgets() {
  wp_add_dashboard_widget(
    'wp_dashboard_widget',
    'E9 Utils',
    'e9_utils_dashboard_widget_function' );
}
add_action('wp_dashboard_setup', 'e9_utils_add_dashboard_widgets' );
