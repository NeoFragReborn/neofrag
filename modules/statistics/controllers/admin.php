<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Modern statistics admin v0.4 — stat cards with trends + filter bar + chart card.
 *
 * couplage(forum): la carte « Messages forum » n'existe que si le module est installé
 * (`$this->module('forum')`), et _safe_count() ne lit jamais une table absente : la couche base de
 * données écrit son alerte au journal AVANT de lever l'erreur, qu'un try/catch ne rattrape
 * qu'après coup (même défaut que le tableau de bord, 2026-10-04).
 */

namespace NF\Modules\Statistics\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->js('chart.umd.min')
				->js('statistics');

		// Stats with comparison (current vs prior period)
		$stats = $this->_collect_stats();

		$cards = '<div class="nf-stats-grid">';
		foreach ($stats as $s)
		{
			$cards .= '<div class="nf-stat-card">';
			$cards .= '<div class="nf-stat-label"><i class="'.$s['icon'].'"></i> '.nf_texte($s['label']).'</div>';
			$cards .= '<div class="nf-stat-value">'.$s['value'].'</div>';
			if (!empty($s['trend']))
			{
				$cards .= '<div class="nf-stat-trend '.($s['trend_class'] ?? '').'">';
				if (!empty($s['trend_icon'])) $cards .= '<i class="'.$s['trend_icon'].'"></i> ';
				$cards .= nf_texte($s['trend']);
				$cards .= '</div>';
			}
			$cards .= '</div>';
		}
		$cards .= '</div>';

		// Filter bar (horizontal, compact) — wraps NeoFrag's filter form
		$filter_form = $this	->form()
								->set_id('sq6fswkfb81n0lu4cb7eyb3tuixcovla')
								->add_rules('statistics')
								->fast_mode()
								->display();

		$filter_card = '<div class="card stats-filter-card">'
			.'<div class="nf-card-header">'
			.'<span><i class="fas fa-filter"></i> '.$this->lang('Filtres').'</span>'
			.'<span class="stats-presets">'
			.'<button type="button" class="btn btn-outline-secondary btn-sm" data-stats-preset="7">'.$this->lang('7 jours').'</button>'
			.'<button type="button" class="btn btn-outline-secondary btn-sm" data-stats-preset="30">'.$this->lang('30 jours').'</button>'
			.'<button type="button" class="btn btn-outline-secondary btn-sm" data-stats-preset="90">'.$this->lang('90 jours').'</button>'
			.'<button type="button" class="btn btn-outline-secondary btn-sm" data-stats-preset="365">'.$this->lang('1 an').'</button>'
			.'</span>'
			.'</div>'
			.'<div class="card-body stats-filter-body">'.$filter_form.'</div>'
			.'</div>';

		// Chart card — full width
		$chart_card = '<div class="card stats-chart-card">'
			.'<div class="nf-card-header">'
			.'<span><i class="fas fa-chart-line"></i> '.$this->lang('Évolution dans le temps').'</span>'
			.'</div>'
			.'<div class="stats-chart-body" style="position:relative;height:440px;width:100%;"><canvas id="stats-chart"></canvas></div>'
			.'</div>';

		// Inline JS for preset buttons
		$preset_js = <<<JS
<script>
(function(){
	document.querySelectorAll('[data-stats-preset]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var days = parseInt(btn.getAttribute('data-stats-preset'), 10);
			var to = new Date();
			var from = new Date(); from.setDate(from.getDate() - days);
			// Le sélecteur écrit la date dans le format de la langue (« 02.10.2026 » en allemand).
			var poser = function(input, date) {
				if (!input) return;
				if (input._flatpickr) { input._flatpickr.setDate(date, false); }
				else { input.value = String(date.getDate()).padStart(2,'0') + '/' + String(date.getMonth()+1).padStart(2,'0') + '/' + date.getFullYear(); }
			};
			var startIn = document.querySelector('input[name$="[start]"], input[name="start"]');
			var endIn = document.querySelector('input[name$="[end]"], input[name="end"]');
			poser(startIn, from);
			poser(endIn, to);
			// Rafraîchit le graphe (statistics.js écoute 'change' sur les champs du form).
			if (startIn) startIn.dispatchEvent(new Event('change', {bubbles:true}));
			if (endIn) endIn.dispatchEvent(new Event('change', {bubbles:true}));
		});
	});
})();
</script>
JS;

		return $cards.$filter_card.$chart_card.$preset_js;
	}

	private function _safe_count($table, array $where = [])
	{
		if (!$this->db->table_exists($table))
		{
			return 0;
		}

		try {
			$q = $this->db->from($table);
			foreach ($where as $w) {
				if (count($w) === 1) $q->where($w[0]);
				else $q->where($w[0], $w[1]);
			}
			return (int)$q->count();
		} catch (\Throwable $e) { return 0; }
	}

	private function _collect_stats()
	{
		$out = [];

		// Compare current 30d vs prior 30d
		$visitors_30  = $this->_safe_count('nf_session', [['last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)']]);
		$visitors_60  = $this->_safe_count('nf_session', [
			['last_activity > DATE_SUB(NOW(), INTERVAL 60 DAY)'],
			['last_activity <= DATE_SUB(NOW(), INTERVAL 30 DAY)']
		]);
		$visitors_diff = $this->_pct_diff($visitors_30, $visitors_60);
		$out[] = [
			'label' => $this->lang('Visiteurs (30j)'),
			'icon'  => 'fas fa-users',
			'value' => number_format($visitors_30, 0, ',', ' '),
			'trend' => $visitors_diff['text'],
			'trend_class' => $visitors_diff['class'],
			'trend_icon'  => $visitors_diff['icon']
		];

		$reg_30 = $this->_safe_count('nf_user', [
			['deleted', FALSE],
			['registration_date > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))']
		]);
		$reg_60 = $this->_safe_count('nf_user', [
			['deleted', FALSE],
			['registration_date > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 60 DAY))'],
			['registration_date <= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))']
		]);
		$reg_diff = $this->_pct_diff($reg_30, $reg_60);
		$out[] = [
			'label' => $this->lang('Inscriptions (30j)'),
			'icon'  => 'fas fa-user-plus',
			'value' => number_format($reg_30, 0, ',', ' '),
			'trend' => $reg_diff['text'],
			'trend_class' => $reg_diff['class'],
			'trend_icon'  => $reg_diff['icon']
		];

		if ($this->module('forum'))
		{
			$forum_30 = $this->_safe_count('nf_forum_messages', [
				['date > DATE_SUB(NOW(), INTERVAL 30 DAY)']
			]);
			$forum_60 = $this->_safe_count('nf_forum_messages', [
				['date > DATE_SUB(NOW(), INTERVAL 60 DAY)'],
				['date <= DATE_SUB(NOW(), INTERVAL 30 DAY)']
			]);
			$forum_diff = $this->_pct_diff($forum_30, $forum_60);
			$out[] = [
				'label' => $this->lang('Messages forum (30j)'),
				'icon'  => 'fas fa-comments',
				'value' => number_format($forum_30, 0, ',', ' '),
				'trend' => $forum_diff['text'],
				'trend_class' => $forum_diff['class'],
				'trend_icon'  => $forum_diff['icon']
			];
		}

		$com_30 = $this->_safe_count('nf_comment', [['date > DATE_SUB(NOW(), INTERVAL 30 DAY)']]);
		$com_60 = $this->_safe_count('nf_comment', [
			['date > DATE_SUB(NOW(), INTERVAL 60 DAY)'],
			['date <= DATE_SUB(NOW(), INTERVAL 30 DAY)']
		]);
		$com_diff = $this->_pct_diff($com_30, $com_60);
		$out[] = [
			'label' => $this->lang('Commentaires (30j)'),
			'icon'  => 'far fa-comments',
			'value' => number_format($com_30, 0, ',', ' '),
			'trend' => $com_diff['text'],
			'trend_class' => $com_diff['class'],
			'trend_icon'  => $com_diff['icon']
		];

		return $out;
	}

	private function _pct_diff($current, $prior)
	{
		if ($prior === 0)
		{
			if ($current === 0)
			{
				return ['text' => $this->lang('Pas de données'), 'class' => '', 'icon' => ''];
			}
			return ['text' => '+'.$current.' '.$this->lang('vs aucun précédent'), 'class' => 'up', 'icon' => 'fas fa-arrow-up'];
		}
		$pct = round((($current - $prior) / $prior) * 100, 1);
		if ($pct === 0.0 || $pct === 0)
		{
			return ['text' => $this->lang('Identique au précédent'), 'class' => '', 'icon' => 'fas fa-minus'];
		}
		$sign = $pct > 0 ? '+' : '';
		$text = $sign.$pct.'% '.$this->lang('vs 30j précédents');
		if ($pct > 0)
		{
			return ['text' => $text, 'class' => 'up', 'icon' => 'fas fa-arrow-up'];
		}
		return ['text' => $text, 'class' => 'down', 'icon' => 'fas fa-arrow-down'];
	}
}
