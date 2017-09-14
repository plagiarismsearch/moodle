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
 * Record the fact that scanning is now complete for a file on the server
 *
 * @package    plagiarism_plagiarismsearch
 * @author     Alex Crosby developer@plagiarismsearch.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/plagiarism/plagiarismsearch/lib.php');

$rid = optional_param('id', null, PARAM_INT);
$key = optional_param('api_key', null, PARAM_TEXT);
$report = optional_param('report', null, PARAM_TEXT);

if (empty($rid) or empty($key) or empty($report)) {
    die();
}

if ($key != plagiarismsearch_config::get_settings('api_key')) {
    die();
}

if (!$localreport = plagiarismsearch_reports::get_one(array('rid' => $rid))) {
    die();
}

if ($report = json_decode($report)) {
    $values = array(
        'plagiarism' => $report->plagiat,
        'status' => $report->status,
        'url' => $report->file,
    );

    if (plagiarismsearch_reports::update($values, $localreport->id)) {
        die($rid);
    }
} else {
    // JSON error

    $values = array(
        'status' => plagiarismsearch_reports::STATUS_ERROR,
        'log' => 'Sync JSON error'
    );

    plagiarismsearch_reports::update($values, $localreport->id);
}