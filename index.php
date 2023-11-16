<?php
/**
 * Plugin Name: PCSoft Core Setup
 * Plugin Description: nginx unit server setup and running
 * Author: PCSoftNepal
 * Version: 1.0
 */

require_once('lib/Pcsoftcli.php');
add_action( 'cli_init', 'pc_cli_register_commands' );

/**
 * Registers our command when cli get's initialized.
 *
 * @since  1.0.0
 * @author Pramod
 */
function pc_cli_register_commands() {
	WP_CLI::add_command( 'pcsoft', 'PCSOFT_CLI' );
}