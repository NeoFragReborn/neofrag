<?php
declare(strict_types=1);
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
 * - Echappement XSS systématique via htmlspecialchars((string) ()).
 */

namespace NF\NeoFrag\Traits;

trait Admin_Helpers
{
	/**
	 * Jeton CSRF de session pour les actions MUTANTES déclenchées par lien GET
	 * (delete / toggle / close / restore…). SameSite=Lax n'arrête pas une navigation
	 * top-level : sans jeton, un simple lien piégé suffit à déclencher l'action sur
	 * un admin connecté. Pour les formulaires, préférer form2/confirm_deletion()
	 * qui portent déjà leur propre jeton.
	 *
	 * Le jeton lui-même est tiré et gardé par nf_jeton_csrf() (neofrag/helpers/input.php), que l'éditeur
	 * riche emploie aussi hors d'un contrôleur.
	 */
	protected function csrf_token()
	{
		return nf_jeton_csrf();
	}

	/** URL d'action mutante : url() + jeton CSRF en query (?_=token). */
	protected function csrf_url($url)
	{
		return \url($url).'?_='.$this->csrf_token();
	}

	/**
	 * Le jeton CSRF de la requête (param `_`, GET ou POST) est-il le bon ? Pour une action qui répond en
	 * JSON et ne peut pas rediriger comme check_csrf() — l'achat de la boutique, le paiement (2026-10-04).
	 * La garde de la CI reconnaît cet appel comme une vérification.
	 */
	protected function csrf_valide(): bool
	{
		$token = $_GET['_'] ?? $_POST['_'] ?? NULL;

		return is_string($token) && hash_equals($this->csrf_token(), $token);
	}

	/**
	 * Rejette la requête si le jeton CSRF (param `_`, GET ou POST) est absent/invalide,
	 * avec redirection vers $redirect. À appeler en TÊTE de toute action mutante.
	 */
	protected function check_csrf($redirect)
	{
		$token = $_GET['_'] ?? $_POST['_'] ?? NULL;

		if (!is_string($token) || !hash_equals($this->csrf_token(), $token))
		{
			notify($this->lang('Action non autorisée (jeton de sécurité invalide).'), 'danger');
			redirect($redirect);
		}
	}

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
			.'<i class="fas fa-arrow-left"></i> '.nf_texte($label)
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
		$html .= '<div class="settings-section-icon"><i class="'.nf_texte($icon).'"></i></div>';
		$html .= '<div class="settings-section-meta">';
		// Sans double encodage : un titre venu d'un formulaire est déjà encodé (« journ&eacute;e »), et
		// s'affichait tel quel — le titre d'une campagne de dons, d'un événement du calendrier.
		$html .= '<div class="settings-section-title">'.nf_texte($title).'</div>';
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
	 * Le bouton qui crée, pour l'en-tête de la carte de la liste qu'il alimente (charte de
	 * l'administration, docs/guide/create-a-module.md) : `admin_card(…, $this->admin_create(…))`.
	 */
	protected function admin_create($url, $label): string
	{
		return '<a class="btn btn-primary btn-sm" href="'.url($url).'"><i class="fas fa-plus"></i> '.$label.'</a>';
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
		$html .= '<i class="'.nf_texte($icon).'"></i>';
		$html .= '<div class="nf-empty-title">'.nf_texte($title).'</div>';
		if ($desc !== '')
		{
			$html .= '<div class="nf-empty-desc">'.nf_texte($desc).'</div>';
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
				$html .= '<i class="'.nf_texte($s['icon']).'"></i> ';
			}
			$html .= nf_texte($s['label']).'</div>';
			$html .= '<div class="nf-stat-value">'.nf_texte($s['value']).'</div>';

			if (!empty($s['trend']))
			{
				$cls = $s['trend_class'] ?? 'flat';
				if (!in_array($cls, ['up', 'down', 'flat', 'warn'], TRUE))
				{
					$cls = 'flat';
				}
				$html .= '<div class="nf-stat-trend '.$cls.'">'.nf_texte($s['trend']).'</div>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Sélecteurs de tri (colonne + sens) pour une grille admin, à insérer dans un <form method="get">
	 * de toolbar. Le tri s'applique à la soumission du form (bouton « Filtrer »), exactement comme les
	 * autres filtres ; il est calculé côté checker via sort_items() (allowlist). Ici on ne fait que l'UI.
	 *
	 * @param array $cols  ['cle' => 'Libellé'] — mêmes clés que l'allowlist passée à sort_items().
	 * @param array $state ['key' => , 'dir' => ] retourné par sort_items().
	 */
	protected function sort_select(array $cols, array $state)
	{
		$html = '<label class="text-muted" style="font-size:12px;display:flex;align-items:center;gap:6px;margin:0;">'
			.'<i class="fas fa-sort"></i> '.$this->lang('Trier').'</label>';

		$html .= '<select name="sort" class="form-select form-select-sm" style="width:auto;">';
		foreach ($cols as $key => $label)
		{
			$html .= '<option value="'.nf_texte($key).'"'.(($state['key'] ?? '') === $key ? ' selected' : '').'>'.nf_texte($label).'</option>';
		}
		$html .= '</select>';

		$html .= '<select name="order" class="form-select form-select-sm" style="width:auto;">';
		$html .= '<option value="desc"'.(($state['dir'] ?? '') === 'desc' ? ' selected' : '').'>'.$this->lang('Décroissant').'</option>';
		$html .= '<option value="asc"'.(($state['dir'] ?? '') === 'asc' ? ' selected' : '').'>'.$this->lang('Croissant').'</option>';
		$html .= '</select>';

		return $html;
	}
}
