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
 * submit a file to plagiarismsearch for analysis
 *
 * @package    plagiarism_plagiarismsearch
 * @author     Alex Crosby developer@plagiarismsearch.com
 * @copyright  @2017 PlagiarismSearch.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/plagiarism/plagiarismsearch/lib.php');
global $CFG, $DB;

$userid = required_param('userid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$filehash = required_param('filehash', PARAM_TEXT);
$force = optional_param('force', 0, PARAM_INT);

require_sesskey();

$url = new moodle_url('/plagiarism/plagiarismsearch/submit.php');
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

$PAGE->set_url($url);
require_login($cm->course, true, $cm);

$context = context_module::instance($cmid);
require_capability('plagiarism/plagiarismsearch:submitlinks', $context);

if (!plagiarismsearch_config::get_settings('use')) {
    // Disabled at the site level
    print_error('disabledsite', 'plagiarism_plagiarismsearch');
}

// Check student permission
if ($force) {
    if (!plagiarism_plugin_plagiarismsearch::has_show_resubmit_link($cmid, $userid, $filehash)) {
        print_error('student_error_nopermission', 'plagiarism_plagiarismsearch');
    }
} else if (!plagiarism_plugin_plagiarismsearch::has_show_submit_link($cmid)) {
    print_error('student_error_nopermission', 'plagiarism_plagiarismsearch');
}

// Retrieve the file and check everything is OK
/* @var $file \stored_file */
$fs = get_file_storage();
if (!$file = $fs->get_file_by_hash($filehash)) {
    print_error('invalidfilehash', 'plagiarism_plagiarismsearch');
}

if ($file->get_contextid() != $context->id) {
    print_error('wrongfilecontext', 'plagiarism_plagiarismsearch');
}

if ($file->get_userid() != $userid) {
    print_error('wrongfileuser', 'plagiarism_plagiarismsearch');
}

// Send file
$msg = plagiarismsearch_core::send_file($file, $cmid, array('force' => $force, 'submit' => 'manual'));

// Safe back redirect
$redirect = plagiarismsearch_core::redirect_url($cm, $context);
redirect($redirect, $msg);
