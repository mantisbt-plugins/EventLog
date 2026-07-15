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
 * A class that represents a user request that has 1 or more events
 * associated with it.
 */
class EventLogRequest
{
	/**
	 * The id of the events request.
	 */
	public $id;

	/**
	 * The timestamp of the events request.
	 */
	public $timestamp;

	/**
	 * The user id.
	 */
	public $user_id;
}

/**
 * Add a request
 *
 * @param integer $p_request_timestamp Request timestamp.
 * @return integer The request id.
 */
function request_add( $p_request_timestamp ) {
	db_param_push();

	$t_requests_table = plugin_table( 'requests' );

	$t_query = "INSERT INTO $t_requests_table ( user_id, timestamp ) VALUES (" . db_param() . ", " . db_param() . ")";

	if ( auth_is_user_authenticated() ) {
		$t_user_id = auth_get_current_user_id();
	} else {
		$t_user_id = 0;
	}

	db_query( $t_query, array( $t_user_id, $p_request_timestamp ) );

	$t_request_id = db_insert_id( $t_requests_table );

	db_param_pop();

	return $t_request_id;
}

/**
 * Gets the total number of event log requests in the database.
 *
 * @return integer the number of event log requests.
 */
function request_count() {
	db_param_push();

	$t_requests_table = plugin_table( 'requests' );

	$t_query = "SELECT count(*) FROM $t_requests_table";
	$t_result = db_query( $t_query );

	$t_count = db_result( $t_result );

	db_param_pop();

	return $t_count;
}

/**
 * Gets the requests on a page given the page number (1 based)
 * and the number of requests per page.
 *
 * @param int $p_page_id   A 1-based page number.
 * @param int $p_per_page  The number of eventsto display per page.
 *
 * @return array The request instances.
 */
function request_get_page( $p_page_id, $p_per_page ) {
	db_param_push();

	$t_requests_table = plugin_table( 'requests' );
	$t_offset = ( $p_page_id - 1 ) * $p_per_page;

	$t_query = "SELECT * FROM $t_requests_table ORDER BY timestamp DESC";
	$t_result = db_query( $t_query, [], $p_per_page, $t_offset );

	$t_requests = array();

	while ( $t_row = db_fetch_array( $t_result ) ) {
		$t_request = new EventLogRequest();
		$t_request->id = (int)$t_row['id'];
		$t_request->user_id = (int)$t_row['user_id'];
		$t_request->timestamp = (int)$t_row['timestamp'];

		$t_requests[] = $t_request;
	}

	db_param_pop();

	return $t_requests;
}

/**
 * Formats an array of requests and uses the events api to retrieve
 * associated events.
 *
 * @param array $p_requests The array of requests to format.
 * @return array The formatted requests.
 */
function request_format( $p_requests ) {
	static $s_date_format = null;

	if ( $s_date_format === null ) {
		$s_date_format = config_get( 'complete_date_format' );
	}

	$t_formatted_requests = array();

	foreach ( $p_requests as $t_request ) {
		$t_formatted_request = array();
		$t_formatted_request['id'] = sprintf( "%d", $t_request->id );
		$t_formatted_request['timestamp'] = date( $s_date_format, $t_request->timestamp );
		$t_formatted_request['user'] = prepare_user_name( $t_request->user_id );

		$t_events = event_get_by_request_id( $t_request->id );
		$t_events_text = '';

		foreach( $t_events as $t_event ) {
			$t_event_text = event_string_to_html( $t_event->event );

			if ( is_blank( $t_events_text ) ) {
				$t_events_text = $t_event_text;
			} else {
				$t_events_text .= '<br />' . $t_event_text;
			}
		}

		$t_formatted_request['events'] = $t_events_text;
		$t_formatted_requests[] = $t_formatted_request;
	}

	return $t_formatted_requests;
}

/**
 * Build the REST representation of a page of event log requests, together with
 * pagination metadata. Each request carries its user reference and, for every
 * associated event, only the sanitized HTML (display links and @U/@P user /
 * project references already resolved). The raw event text is intentionally
 * omitted so a client can never accidentally render unescaped content.
 *
 * Must be called with the EventLog plugin as the current plugin context, since
 * the underlying data functions resolve their table names via plugin_table().
 *
 * @param int $p_page      A 1-based page number.
 * @param int $p_per_page  The number of requests per page.
 * @return array An array with 'requests' and 'pagination' keys.
 */
function request_rest_list( $p_page, $p_per_page ) {
	$t_total = (int)request_count();
	$t_total_pages = max( 1, (int)ceil( $t_total / $p_per_page ) );
	$t_page = min( max( 1, (int)$p_page ), $t_total_pages );

	$t_requests = request_get_page( $t_page, $p_per_page );

	$t_formatted = array();
	foreach ( $t_requests as $t_request ) {
		$t_events = event_get_by_request_id( $t_request->id );

		$t_formatted_events = array();
		foreach ( $t_events as $t_event ) {
			$t_formatted_events[] = array(
				'id' => (int)$t_event->id,
				'timestamp' => ApiObjectFactory::datetimeString( (int)$t_event->timestamp ),
				'event_html' => event_string_to_html( $t_event->event ),
			);
		}

		$t_user_id = (int)$t_request->user_id;
		$t_user = ( $t_user_id > 0 && user_exists( $t_user_id ) )
			? mci_account_get_array_by_id( $t_user_id )
			: null;

		$t_formatted[] = array(
			'id' => (int)$t_request->id,
			'timestamp' => ApiObjectFactory::datetimeString( (int)$t_request->timestamp ),
			'user' => $t_user,
			'events' => $t_formatted_events,
		);
	}

	return array(
		'requests' => $t_formatted,
		'pagination' => array(
			'page' => $t_page,
			'per_page' => (int)$p_per_page,
			'total' => $t_total,
			'total_pages' => $t_total_pages,
		),
	);
}

/**
 * Delete all requests from database.
 * @return void
 */
function request_clear_all() {
	db_param_push();

	$t_requests_table = plugin_table( 'requests' );
	$t_query = "DELETE FROM $t_requests_table";
	db_query( $t_query, array() );

	db_param_pop();
}

/**
 * Delete all requests older than a given timestamp.
 *
 * @param integer $p_timestamp The timestamp.
 * @return void
 */
function request_trim( $p_timestamp ) {
	db_param_push();

	$c_timestamp = (int)$p_timestamp;
	$t_query = "DELETE FROM " . plugin_table( 'requests' ) . " WHERE timestamp < $c_timestamp";
	db_query( $t_query );

	db_param_pop();
}
