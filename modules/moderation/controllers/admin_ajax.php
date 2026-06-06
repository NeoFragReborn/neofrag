<?php
/**
 * https://neofr.ag
 * AJAX endpoints du module Modération.
 * Phase 2 : modal sanction admin (chargé en AJAX dans la page report_detail).
 * Phase 3 : modal report user-side (boutons "Signaler" partout).
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin_Ajax extends Controller_Module
{
	// Méthodes user-side _ajax_report_modal / _ajax_report_submit déplacées vers controllers/ajax.php
	// (NeoFrag route /admin/ajax/* → admin_ajax.php et /ajax/* → ajax.php)
}
