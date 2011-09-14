<?php
/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines http://www.simplemachines.org
 * @copyright 2011 Simple Machines
 * @license http://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.0
 */

/**
 * generate list of "likers" for the message id in ;m and output it
 * @return void
 *
 * todo: support pagination
 */
function LikeDispatch()
{
	global $context, $board, $memberContext, $sourcedir, $txt;

	$xml = isset($_REQUEST['xml']) ? true : false;
	$action = isset($_REQUEST['sa']) ? $_REQUEST['sa'] : '';
	if($action === '')
		$action = 'getlikes';
	$ctype = isset($_REQUEST['ctype']) ? $_REQUEST['ctype'] : 1;		// default to content type = 1 (post)
	$mid = isset($_REQUEST['m']) ? (int)$_REQUEST['m'] : 0;

	if($mid) {
		if(!isset($board) || !$board) {
			$request = smf_db_query('SELECT m.id_topic, t.id_board FROM {db_prefix}messages AS m
				LEFT JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)
				WHERE m.id_msg = {int:id_msg}',
				array('id_msg' => $mid));
			$row = mysql_fetch_assoc($request);
			mysql_free_result($request);
			$board = $row ? $row['id_board'] : 0;
		}
		$allowed = isset($board) && $board && allowedTo('see_like', $board);
		if(!$allowed) {		// something is wrong...
			require_once($sourcedir . '/Xml.php');
			loadTemplate('Xml');
			AjaxErrorMsg($txt['no_access'], $xml);
		}
		$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
		$users = array();
		if($action === 'getlikes') {
			$request = smf_db_query('
				SELECT l.* FROM {db_prefix}likes AS l WHERE l.id_msg = {int:idmsg} AND l.ctype = {int:ctype}
					ORDER BY l.updated DESC LIMIT {int:start}, 20',
				array('idmsg' => $mid, 'ctype' => $ctype, 'start' => $start));

			while($row = mysql_fetch_assoc($request)) {
				$row['dateline'] = timeformat($row['updated']);
				$users[] = $row['id_user'];
				$context['likes'][$row['id_user']] = $row;
			}
			mysql_free_result($request);
			loadMemberData($users);
			foreach($users as $user) {
				loadMemberContext($user);
				$context['likes'][$user]['member'] = &$memberContext[$user];
			}
		}
		loadLanguage('Like');
		loadTemplate('LikeSystem');
		loadTemplate('GenericBits');
		$context['sub_template'] = 'getlikes';
		if($xml)
			$context['xml'] = true;
	}
}
?>

