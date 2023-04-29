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

/**
 * A class that contains all the information relating to a tweet.
 */
class EventLog
{
	/**
	 * The id of the event in the database.
	 */
	var $id = 0;

	/**
	 * The string of the event.
	 */
	var $event = '';

	/**
	 * The timestamp at which the event was generated. 
	 */
	var $timestamp = null;
}

/**
 * Adds a tweet.  This functional sets the submitted / last updated timestamps to now.
 *
 * @param integer $p_request_id  The page request id.
 * @param string $p_event_text   The text of the event log.
 * @return integer Id of the added event, or -1 if event skipped. 
 */
function event_add($p_request_id, $p_event_text ) {
	$t_events_table = plugin_table( 'events' );

	$t_query = "INSERT INTO $t_events_table ( request_id, event, timestamp ) VALUES (" . db_param() . ", " . db_param() . ", '" . db_now() . "')";

	db_query( $t_query, array( $p_request_id, trim( $p_event_text ) ) );

	return db_insert_id( $t_events_table );
}

/**
 * Gets the events associated with the specified request id.
 * @param integer $p_request_id The request id.
 * @return array events associated with specified request id.
 */
function event_get_by_request_id( $p_request_id ) {
	$t_events_table = plugin_table( 'events' );

	$t_query = 'SELECT * FROM ' . $t_events_table . ' WHERE request_id=' . db_param() . ' ORDER BY timestamp ASC';
	$t_result = db_query( $t_query, array( $p_request_id ) );

	$t_events = array();

	while ( $t_row = db_fetch_array( $t_result ) ) {
		$t_event = new EventLog();
		$t_event->id = (int)$t_row['id'];
		$t_event->event = $t_row['event'];
		$t_event->timestamp = (int)$t_row['timestamp'];

		$t_events[] = $t_event;
	}

	return $t_events;
}

/**
 * Clears the event log.
 * @return void
 */
function event_clear_all() {
	$t_events_table = plugin_table( 'events' );

	$t_query = "DELETE FROM $t_events_table";

	db_query( $t_query, array() );
}

/**
 * Process string to replace tag with ids with corresponding links.
 *
 * @param string $p_string the string to process
 * @param string $p_tag The tag, e.g. '@U' for user or '@P' for project.
 * @param string $p_type 'user' or 'project'
 *
 * @return string the processed string.
 */
function string_process_generic_link( $p_string, $p_tag, $p_type ) {
	static $s_callback;
	if( $s_callback === null ) {
		/**
		 * Replace user (@Uxxx) or project ID (@Pxxx) references.
		 *
		 * @param array $p_matches
		 *
		 * @return mixed|string
		 * @uses string $p_type 'user' or 'project'
		 */
		$s_callback = function( array $p_matches ) use ( $p_type ) {
			$t_exists_function = $p_type . '_exists';
			if( $t_exists_function( (int)$p_matches[2] ) ) {
				$t_get_field_function = $p_type . '_get_field';
				$t_field_name = $p_type == 'user' ? 'username' : 'name';
				$t_value = $t_get_field_function( (int)$p_matches[2], $t_field_name );
				return ' <strong>' . $t_value . '</strong>';
			} else {
				return $p_matches[2];
			}
		};
	}

	# bail if the link tag is blank
	if ( '' == $p_tag || $p_string == '' ) {
		return $p_string;
	}

	return preg_replace_callback( '/(^|\W)' . preg_quote( $p_tag, '/' ) . '(\d+)\b/',
		$s_callback,
		$p_string
	);
}

