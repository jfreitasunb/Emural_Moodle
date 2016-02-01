<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Login with E-mural credential.
 *
 * @package auth_emural
 * @author Phelippe Amorim
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

define('EMURAL_URL', $CFG->wwwemural);
define('EMURAL_SERVICES', EMURAL_URL.'services/');
define('EMURAL_SERVICE_USER', EMURAL_SERVICES.'user.php');
define('EMURAL_SERVICE_AUTHENTICATION', EMURAL_SERVICES.'auth.php');

/**
 * Plugin for emural authentication.
 */
class auth_plugin_emural extends auth_plugin_base {

    /**
     * Constructor.
     */
    function auth_plugin_emural() {
        $this->authtype = 'emural';
        $this->config = get_config('auth/emural');
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {

		$data = array(
		    'user' => $username,
		    'password' => $password
		);

		// Create a connection
		$ch = curl_init(EMURAL_SERVICE_AUTHENTICATION);

		// Form data string
		$postString = http_build_query($data, '', '&');

		// Setting our options
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Get the response
		$response = curl_exec($ch);
		curl_close($ch);

		$response_obj = json_decode($response);

		if ($response_obj->success) {
			return true;
		} else {
			switch ($response_obj->reason) {
				case 'PSS':
					print_error('auth_emural_without_password', "auth_{$this->authtype}", EMURAL_URL);
				break;

				case 'NPE':
					print_error('auth_emural_missed_email', "auth_{$this->authtype}", EMURAL_URL);
				break;
			}
		}

    	return false;
    }


    function user_signup($user, $notify=true) {
		print_error('auth_emural_missed_email', 'auth_emural');
	}

    /**
     * Read user information from external database and returns it as array().
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honour synchronisation flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    function get_userinfo($username) {
        $data = array(
		    'user' => $username
		);

		// Create a connection
		$ch = curl_init(EMURAL_SERVICE_USER);

		// Form data string
		$postString = http_build_query($data, '', '&');

		// Setting our options
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Get the response
		$response = curl_exec($ch);
		curl_close($ch);

		$obj = json_decode($response);

		$user = array();
		$user['username'] = $username;
		$nome_array = explode(' ', $obj->nome);
		$user['firstname'] = $nome_array[0];
		if (count($nome_array) > 1) {
			$user['lastname'] = $nome_array[count($nome_array) - 1];
		} else {
			$user['lastname'] = " ";
		}
		$user['email'] = $obj->email;

		return $user;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return false;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    function can_be_manually_set() {
        return false;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        return true;
    }

	function is_synchronised_with_external() {
        return true;
    }
}


