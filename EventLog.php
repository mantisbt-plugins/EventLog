<?php
# Mantis - a php based bugtracking system

# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
# Copyright (C) 2002 - 2008  Mantis Team   - mantisbt-dev@lists.sourceforge.net

# Mantis is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# Mantis is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Mantis.  If not, see <http://www.gnu.org/licenses/>.

require_once( config_get( 'absolute_path' ) . 'core.php' );
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
require_once( config_get( 'plugin_path' ) . 'EventLog/core/request_api.php' );
require_once( config_get( 'plugin_path' ) . 'EventLog/core/event_api.php' );

/**
 * A plugin that manages an event log in the database.
 */
class EventLogPlugin extends MantisPlugin {
	private $request_id = null;
	private $request_timestamp = null;

	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register() {
		$this->name		= plugin_lang_get( 'title' );
		$this->description	= plugin_lang_get( 'description' );

		$this->version		= '1.1';
		$this->requires		= array(
			'MantisCore' => '2.0.0',
		);

		$this->author		= 'Victor Boctor';
		$this->contact		= 'victor@mantishub.net';
		$this->url			= 'http://www.mantishub.com';
	}

	/**
	 * Gets the plugin default configuration.
	 */
	function config() {
		return array(
			'view_threshold'	=>	ADMINISTRATOR,
			'manage_threshold'	=>	ADMINISTRATOR,
			'delete_after_in_days' => 1,
			'retention_days' => 7
		);
	}

	/**
	 * Gets the database schema of the plugin.
	 */
	function schema() {
		return array(
			array( 'CreateTableSQL',
				array( plugin_table( 'events' ), "
					id				I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
					user_id			I		NOTNULL UNSIGNED DEFAULT '0',
					event			C(250)	NOTNULL,
					timestamp		T		NOTNULL
				" )
			),
			array( 'DropTableSQL',
				array( plugin_table( 'events' ) )
			),
			array( 'CreateTableSQL',
				array( plugin_table( 'events' ), "
					id				I NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
					user_id			I NOTNULL UNSIGNED DEFAULT '0',
					event			C(250) NOTNULL,
					timestamp		I UNSIGNED NOTNULL DEFAULT '0'
				" )
			),
			array( 'DropTableSQL',
				array( plugin_table( 'events' ) )
			),
			array( 'CreateTableSQL',
				array( plugin_table( 'requests' ), "
					id				I NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
					timestamp		I UNSIGNED NOTNULL DEFAULT '0',
					user_id			I NOTNULL UNSIGNED DEFAULT '0'
				" )
			),
			array( 'CreateIndexSQL',
				array( 'idx_timestamp', plugin_table( 'requests' ), 'timestamp' )
			),
			array( 'CreateTableSQL',
				array( plugin_table( 'events' ), "
					id				I NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
					request_id		I NOTNULL UNSIGNED DEFAULT '0',
					timestamp		I UNSIGNED NOTNULL DEFAULT '0',
					event			C(250) NOTNULL
				" )
			),
			array( 'CreateIndexSQL',
				array( 'idx_request_id', plugin_table( 'events' ), 'request_id' )
			),
			array( 'AlterColumnSQL',
				array(
					plugin_table( 'events' ),
					"event XL NULL DEFAULT NULL"
				)
			)
		);
	}

	/**
	 * Event hook declaration.
	 * 
	 * @return array An associated array that maps event names to handler names.
	 */
	function hooks() {
		return array(
			'EVENT_MENU_MANAGE' => 'process_main_menu', # Main Menu
			'EVENT_LOG' => 'process_log',
			'EVENT_CRONJOB' => 'trim_events',
			'EVENT_REPORT_BUG_FORM_TOP' => 'trim_events' # just in case cronjob not setup
		);
	}

	/**
	 * Delete requests and events older than N days.
	 */
	function trim_events() {
		$t_retention_days = plugin_config_get( 'retention_days' );
		if ( $t_retention_days > 0 ) {
			$t_trim_timestamp = time() - ( $t_retention_days * 24 * 60 * 60 );
			request_trim( $t_trim_timestamp );
			event_trim( $t_trim_timestamp );
		}
	}

	function process_log( $p_event_name, $p_event_string ) {
		if ( is_blank( $p_event_string ) ) {
			return;
		}

		if ( $this->request_timestamp === null ) {
			$this->request_timestamp = time();
			$this->request_id = request_add( $this->request_timestamp );
		}

		event_add( $this->request_id, $p_event_string );
	}

	/**
	 * If current logged in user can view EventLog, then add a menu option to the main menu.
	 * 
	 * @return array An array containing the hyper link.
	 */
	function process_main_menu() {
		if ( access_has_global_level( plugin_config_get( 'view_threshold' ) ) ) {
			return array( '<a href="' . plugin_page( 'index.php' ) . '">' . plugin_lang_get( 'menu_item' ) . '</a>' );
		}

		return array();
	}
}
