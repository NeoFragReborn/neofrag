<?php
/**
 * https://neofr.ag
 * NeoFrag — Trait Admin_Helpers (R2.1, 2026-05-06)
 *
 * Helpers de présentation pour les contrôleurs admin. Centralise la génération HTML des composants
 * standards (cards, retour, layout split, action bar, empty state, stats) en s'appuyant strictement
 * sur les classes CSS existantes du theme admin :
 *   .settings-section-card / .settings-section-header / .settings-section-icon / .settings-section-meta
 *   .settings-section-title / .settings-section-subtitle / .settings-section-body / .settings-section-row
 *   .settings-section-back / .settings-hub-grid / .settings-hub-card--<color> / .settings-hub-icon
 *   .nf-action-bar / .nf-section-actions / .nf-empty / .nf-stats-grid / .nf-stat-{label|value|trend}
 *
 * Inclu via `use \NF\NeoFrag\Traits\Admin_Helpers;` dans neofrag/loadables/controllers/module.php
 * → disponible automatiquement sur tous les contrôleurs admin (ext. Module).
 *
 * Doctrine :
 * - Aucune classe CSS inventée — tout existe dans themes/admin/css/style.css.
 * - Compose les helpers NeoFrag existants (button_create, button_update, button_delete, button_access).
 * - Aria-labels sur les boutons icon-only.
 * - Echappement XSS systématique via htmlspecialchars().
 */

namespace NF\NeoFrag\Traits;

trait Admin_Helpers
{
	/**
	 * Bouton retour standardisé (.settings-section-back du theme admin).
	 *
	 * @param string $url    URL relative (sera passée à url())
	 * @param string $label  Libellé visible — défaut "Retour"
	 */
	protected function admin_back($url, $label = '')
	{
		if ($label === '')
		{
			$label = $this->lang('Retour');
		}

		return '<a class="settings-section-back" href="'.\url($url).'">'
			.'<i class="fas fa-arrow-left"></i> '.htmlspecialchars($label)
			.'</a>';
	}

	/**
	 * Card d'admin standard (.settings-section-card avec header + body).
	 *
	 * @param string $icon     Classe FontAwesome (ex: 'fas fa-newspaper')
	 * @param string $title    Titre de la card
	 * @param string $body     HTML du corps (déjà rendu)
	 * @param string $subtitle Sous-titre optionnel (ex: '12 articles')
	 * @param string $actions  HTML des boutons à droite du header (ex: bouton Créer)
	 * @param bool   $no_pad   Si TRUE, supprime le padding du body (utile pour tables pleine largeur)
	 */
	protected function admin_card($icon, $title, $body, $subtitle = '', $actions = '', $no_pad = FALSE)
	{
		$body_style = $no_pad ? ' style="padding:0"' : '';

		$html  = '<div class="settings-section-card">';
		$html .= '<div class="settings-section-header">';
		$html .= '<div class="settings-section-icon"><i class="'.htmlspecialchars($icon).'"></i></div>';
		$html .= '<div class="settings-section-meta">';
		$html .= '<div class="settings-section-title">'.htmlspecialchars($title).'</div>';
		if ($subtitle !== '')
		{
			$html .= '<div class="settings-section-subtitle">'.$subtitle.'</div>';
		}
		$html .= '</div>';
		if ($actions !== '')
		{
			$html .= '<div class="nf-section-actions">'.$actions.'</div>';
		}
		$html .= '</div>';
		$html .= '<div class="settings-section-body"'.$body_style.'>'.$body.'</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Layout 2 colonnes (.settings-section-row : grid 1.4fr/1fr responsive built-in à 992px).
	 * Le bouton retour est généré et inclus en tête.
	 *
	 * @param string $back_url    URL pour le bouton retour
	 * @param string $back_label  Libellé du bouton retour
	 * @param string $main        HTML colonne principale (généralement form ou liste)
	 * @param string $aside       HTML colonne secondaire (preview, sidebar) — vide = pas d'aside
	 */
	protected function admin_split($back_url, $back_label, $main, $aside = '')
	{
		$html = $this->admin_back($back_url, $back_label);

		if ($aside !== '')
		{
			$html .= '<div class="settings-section-row">';
			$html .= '<div class="settings-section-main">'.$main.'</div>';
			$html .= '<div class="settings-section-aside">'.$aside.'</div>';
			$html .= '</div>';
		}
		else
		{
			$html .= '<div class="settings-section-main">'.$main.'</div>';
		}

		return $html;
	}

	/**
	 * Barre d'actions générique en haut de page (.nf-action-bar).
	 *
	 * @param array $buttons Tableau de chaînes HTML (boutons déjà rendus via $this->button() ou similaire)
	 */
	protected function admin_action_bar(array $buttons)
	{
		if (empty($buttons))
		{
			return '';
		}

		return '<div class="nf-action-bar">'.implode('', array_map(function($b){
			return (string)$b;
		}, $buttons)).'</div>';
	}

	/**
	 * Empty state standardisé (.nf-empty).
	 *
	 * @param string $icon  Classe FontAwesome (ex: 'far fa-newspaper')
	 * @param string $title Titre principal (ex: "Aucun article.")
	 * @param string $desc  Description optionnelle
	 * @param string $cta   HTML d'un bouton d'action optionnel (ex: button_create)
	 */
	protected function admin_empty($icon, $title, $desc = '', $cta = '')
	{
		$html  = '<div class="nf-empty">';
		$html .= '<i class="'.htmlspecialchars($icon).'"></i>';
		$html .= '<div class="nf-empty-title">'.htmlspecialchars($title).'</div>';
		if ($desc !== '')
		{
			$html .= '<div class="nf-empty-desc">'.htmlspecialchars($desc).'</div>';
		}
		if ($cta !== '')
		{
			$html .= '<div style="margin-top:12px;">'.$cta.'</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Grille de stat cards (.nf-stats-grid).
	 *
	 * @param array $stats Tableau de ['label', 'value', 'icon' (optionnel), 'trend' (optionnel),
	 *                                  'trend_class' => 'up|down|flat|warn']
	 */
	protected function admin_stats(array $stats)
	{
		if (empty($stats))
		{
			return '';
		}

		$html = '<div class="nf-stats-grid">';

		foreach ($stats as $s)
		{
			$html .= '<div class="nf-stat-card">';
			$html .= '<div class="nf-stat-label">';
			if (!empty($s['icon']))
			{
				$html .= '<i class="'.htmlspecialchars($s['icon']).'"></i> ';
			}
			$html .= htmlspecialchars($s['label']).'</div>';
			$html .= '<div class="nf-stat-value">'.htmlspecialchars((string)$s['value']).'</div>';

			if (!empty($s['trend']))
			{
				$cls = $s['trend_class'] ?? 'flat';
				if (!in_array($cls, ['up', 'down', 'flat', 'warn'], TRUE))
				{
					$cls = 'flat';
				}
				$html .= '<div class="nf-stat-trend '.$cls.'">'.htmlspecialchars($s['trend']).'</div>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}
}
