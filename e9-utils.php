<?php
/*
 * Plugin Name: e9-utils
 * GitHub Plugin URI: je9/e9-utils
 * Description: E9 tools and improvements for WordPress.
 * Version: 0.1.6
 * Author: justin@e9.nz
 * Author URI: http://e9.nz
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * RequiresWP: 4.5
 * RequiresPHP: 5.3.0
*/

function e9_utils_dashboard_widget_function() {
  global $wpdb;

  date_default_timezone_set( 'NZ' );

  $query = "SELECT post_modified
    FROM $wpdb->posts
    WHERE post_title <> 'Auto Draft'
    ORDER BY post_date DESC
    LIMIT 1;
  ";

  $site_mod_date = $wpdb->get_var( $query );
  $site_mod_weekday = date( 'w', strtotime( $site_mod_date ) );
  $site_mod_hour = intval( date( 'H', strtotime( $site_mod_date ) ) );

  $current_date = date( 'Y-m-d H:i:s' );

  $backup_days = [
    'sunday last',
    'tuesday this',
    'thursday this',
    'friday this',
    'saturday this',
    'sunday this' ];

  function get_date_from_desc( $day_desc, $hour = 12 ) {
    return date(
      "Y-m-d H:i:s",
      strtotime( $day_desc . ' week' ) + 60 * 60 * $hour );
  }

  $last_backup_date = get_date_from_desc( $backup_days[ 0 ] );
  $next_backup_date = $last_backup_date;

  for ( $i = 0, $l = count( $backup_days ); $i < $l; $i++ ) :
    $day_bu_time = get_date_from_desc( $backup_days[ $i ] );
    if ( strtotime( $current_date ) > strtotime( $day_bu_time ) ) :
      $last_backup_date = $day_bu_time;
      $next_backup_date = get_date_from_desc( $backup_days[ $i + 1 ] );
    else :
      break;
    endif;
  endfor;

  $time_difference =
    strtotime( $last_backup_date ) - strtotime( $site_mod_date );
  $time_difference = floor( $time_difference / 60 / 60 );
  $time_difference_next =
    strtotime( $next_backup_date ) - strtotime( $site_mod_date );
  $time_difference_next = floor( $time_difference_next / 60 / 60 );
  // backups occur between 6am and midday. So we need a 6 hr gap to ensure that
  // the backup was done after the edit for mornings.
  $time_message = '<span class="backup-stop">
    Backup again before updates</span>';
  if ( $time_difference >= 0 || $time_difference_next > -6 ) :
    $time_message = '<span class="backup-maybe">
      New backup may be required before updates</span><br>
      <span class="light">Content was changed close to the backup time,
      or a backup might be completed this morning</span>';
    if ( $time_difference >= 6 ) :
      $time_message = '<span class="backup-go">Backup is up to date</span>';
    endif;
  endif;

?>
<style>
  .e9-utils dd {
    font-weight: 700;
  }
  .e9-utils .backup-stop {
    color: #c11;
  }
  .e9-utils .backup-maybe {
    color: #c81;
  }
  .e9-utils .backup-go {
    color: #1c8;
  }
  .e9-utils .light {
    font-weight: 400;
  }
</style>
<dl class="e9-utils">
  <dt>Last content edit: </dt>
  <dd><?php echo $site_mod_date; ?></dd>
  <dt>Last S3 backup: </dt>
  <dd>
    <?php echo substr( $last_backup_date, 0, 11); ?>
    (Between 06:00 and 12:00)</dd>
  <dt>Next S3 backup: </dt>
  <dd>
    <?php echo substr( $next_backup_date, 0, 11); ?>
    (Between 06:00 and 12:00)</dd>
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
add_action( 'wp_dashboard_setup', 'e9_utils_add_dashboard_widgets' );
