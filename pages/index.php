<?php
# Copyright (C) 2008	Victor Boctor
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

require_once( config_get( 'plugin_path' ) . 'EventLog/core/request_api.php' );
require_once( config_get( 'plugin_path' ) . 'EventLog/core/event_api.php' );

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );

$f_page_id = gpc_get_int( 'page_id', 1 );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

$t_per_page = 100;
$t_total_count = request_count();
$t_total_pages_count = (integer)(( $t_total_count + ( $t_per_page - 1 ) ) / $t_per_page);

$t_requests = request_get_page( $f_page_id, $t_per_page );
$t_formatted_requests = request_format( $t_requests );

echo '<br /><div align="center">';

if ( $f_page_id > 1 ) {
	echo '[ <a href="', plugin_page( 'index' ), '&amp;page_id=', (int)($f_page_id) - 1, '">', plugin_lang_get( 'newer_events' ), '</a> ]&nbsp;';
}

if ( $f_page_id < $t_total_pages_count ) {
	echo '[ <a href="', plugin_page( 'index' ), '&amp;page_id=', (int)( $f_page_id ) + 1, '">', plugin_lang_get( 'older_events' ), '</a> ]';
}

echo '</div>';

echo '<div align="center">';
echo '<form method="post" action="', plugin_page ( 'eventlog_clear' ), '">';
echo '<input type="submit" name="submit" value="', plugin_lang_get( 'clear_events' ), '" />';
echo '</form>';
echo '</div>';

echo '<div class="table-container">';
echo '<table class="width100">';
echo '<thead>';
echo '<tr class="row-category">';
echo '<th>', lang_get( 'id' ), '</th>';
echo '<th>', lang_get( 'timestamp' ), '</th>';
echo '<th>', lang_get( 'username' ), '</th>';
echo '<th>', plugin_lang_get( 'event' ), '</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ( $t_formatted_requests as $t_request ) {
	echo '<tr>';
	echo '<td style="vertical-align: top;">', $t_request['id'], '</td>';
	echo '<td style="vertical-align: top;">', $t_request['timestamp'], '</td>';
	echo '<td style="vertical-align: top;">', $t_request['user'], '</td>';
	echo '<td style="vertical-align: top;">', $t_request['events'], '</td>';
	echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';
html_page_bottom1( __FILE__ );