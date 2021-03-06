<?php
/**
 * Clarifications overview
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

$title = 'Clarification Requests';

$jury_member = $username;

require(LIBWWWDIR . '/header.php');
require(LIBWWWDIR . '/clarification.php');

echo "<div class ='container'> <h1>Clarifications</h1>\n\n";

if ( empty($cids) ) {
	warning('No active contest(s)');
	require(LIBWWWDIR . '/footer.php');
	exit;
}

$contestids = $cids;
if ( $cid !== null ) {
    $contestids = array($cid);
}
// cid -1 will never happen, but otherwise the array is empty and that is not supported
if ( empty($contestids) ) $contestids = array(-1);

echo "<p><a href=\"clarification.php\">Send Clarification</a></p>\n";
echo "<p><a href=\"#newrequests\">View New Clarification Requests</a></p>\n";
echo "<p><a href=\"#oldrequests\">View Old Clarification Requests</a></p>\n";
echo "<p><a href=\"#clarifications\">View General Clarifications</a></p>\n\n";

$sqlbody = 'SELECT c.*, cp.shortname, t.name AS toname, f.name AS fromname,
                   co.shortname AS contestshortname
            FROM clarification c
            LEFT JOIN problem p USING(probid)
            LEFT JOIN contestproblem cp USING (probid, cid)
            LEFT JOIN team t ON (t.teamid = c.recipient)
            LEFT JOIN team f ON (f.teamid = c.sender)
            LEFT JOIN contest co USING (cid)
            WHERE c.cid IN (%Ai) ';

$newrequests    = $DB->q($sqlbody .
                         'AND c.sender IS NOT NULL AND c.answered = 0
                          ORDER BY submittime DESC, clarid DESC', $contestids);

$oldrequests    = $DB->q($sqlbody .
                         'AND c.sender IS NOT NULL AND c.answered != 0
                          ORDER BY submittime DESC, clarid DESC', $contestids);

$clarifications = $DB->q($sqlbody .
                         'AND c.sender IS NULL AND ( c.respid IS NULL OR c.recipient IS NULL )
                          ORDER BY submittime DESC, clarid DESC', $contestids);

echo '<ul class="collapsible" data-collapsible="accordion">
  <li>
    <div class="collapsible-header"><span class="new badge"></span>
      <i class="large material-icons">mode_edit</i>'.
    "New Requests:</div>";

if ( $newrequests->count() == 0 ) {
  echo '<div class="collapsible-body">'.
  "<p>No new clarification requests.</p></div>";
} else {
  putClarificationList($newrequests,NULL);
}
echo '</li>';

  echo '<li><div class="collapsible-header"><i class="large material-icons">mode_edit</i>'.
    "Old Requests:</div>";

if ( $oldrequests->count() == 0 ) {
  echo '<div class="collapsible-body">'.
  "<p>No old clarification requests.</p></div>";
} else {
  putClarificationList($oldrequests,NULL);
}
echo '</li>';

echo '<li><div class="collapsible-header"><i class="material-icons">filter_drama</i>'.
    "General Clarifications:</div>";
if ( $clarifications->count() == 0 ) {
  echo '<div class="collapsible-body">'.
  "<p>No general clarifications.</p></div>";
} else {
  putClarificationList($clarifications,NULL);
}
echo '</li>
</ul>';

echo "</div>";
require(LIBWWWDIR . '/footer.php');
