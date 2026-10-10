<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables;

use NF\NeoFrag\NeoFrag;

abstract class Model extends NeoFrag implements \NF\NeoFrag\Loadable
{
	static protected $_objects = [];

	static public function __load($caller, $args = [])
	{
		$name = array_shift($args) ?: $caller->info()->name;

		if (!isset(static::$_objects[$caller_name = get_class($caller)][$name]))
		{
			static::$_objects[$caller_name][$name] = $caller->___load('models', $name, [$caller]);
		}

		return static::$_objects[$caller_name][$name];
	}

	public function __construct($caller)
	{
		$this->__caller = $caller;
	}

	/**
	 * Dépose une donnée à destination du gabarit, si et seulement si la sortie est amorcée.
	 *
	 * Hors requête web — amorçage headless des tests, ligne de commande — il n'y a pas de page à
	 * rendre et `output->data` n'existe pas. Le repli de langue, lui, doit continuer de fonctionner :
	 * il sert à choisir une ligne en base, ce qui vaut dans tous les contextes. On déclare quand on
	 * peut, on ne fatalise jamais pour une donnée d'affichage.
	 */
	private function declarer(string $cle, $valeur): void
	{
		if (($output = $this->output) && isset($output->data))
		{
			$output->data->set('module', $cle, $valeur);
		}
	}

	/**
	 * La langue dans laquelle SERVIR un contenu, qui n'est pas toujours celle demandée.
	 *
	 * LE DÉFAUT QUE CECI CORRIGE. Un contenu rédigé dans une seule langue proposait quand même les
	 * cinq autres dans son sélecteur, et les cinq rendaient 404 : `WHERE lang = 'en'` ne trouvait
	 * rien, et c'était techniquement juste. Mesuré sur la démonstration le 2026-09-21 : douze pages
	 * de détail, soixante adresses mortes sur soixante-douze demandées. Le geste le plus naturel
	 * d'un visiteur étranger tombait sur une page d'erreur.
	 *
	 * CE QU'ON FAIT À LA PLACE. On sert la version qui existe, et la page le dit. Le choix du repli
	 * suit l'ordre d'affichage des langues du site, ce qui le rend stable d'une requête à l'autre :
	 * deux visiteurs voient la même version, et un moteur de recherche aussi.
	 *
	 * CE QU'ON DÉCLARE AU PASSAGE, et pourquoi c'est indissociable. Les langues réellement
	 * disponibles partent dans les données du module, parce que le gabarit en a besoin pour deux
	 * choses qu'on ne peut pas laisser fausses : n'annoncer en `hreflang` que les langues qui
	 * existent, et poser un `canonical` vers l'original quand on sert un repli. Sans cela, un
	 * moteur indexe six adresses pour un seul texte et les traite comme du contenu dupliqué.
	 *
	 * EN ADMINISTRATION, JAMAIS. Un administrateur qui ouvre la version anglaise doit voir qu'elle
	 * est vide, pas la version française : c'est précisément ce qu'il vient remplir. Le repli est
	 * une politesse faite au visiteur, pas une vérité sur les données.
	 *
	 * Le titre d'une AUTRE version dans l'adresse (le sélecteur de langue la garde telle quelle) : nf_bon_titre() mène à
	 * l'adresse de la langue servie, comme tout titre qui n'est pas le bon (m06, 2026-10-10).
	 *
	 * @param  string $table   la table par langue (`nf_news_lang`, `nf_teams_lang`…)
	 * @param  string $colonne la colonne qui porte l'identifiant du contenu (`news_id`…)
	 * @param  mixed  $id      l'identifiant du contenu
	 * @return string le nom de la langue à employer dans le `WHERE`
	 */
	protected function langue_du_contenu(string $table, string $colonne, $id): string
	{
		/*
		 * `config->lang` n'est pas toujours un addon de langue : l'amorçage headless des tests, et
		 * la ligne de commande, laissent une chaîne vide, parce que la langue se choisit sur la
		 * session du visiteur. Appeler `info()` dessus fatalise — ce qui arrivait déjà avant. Sans
		 * langue demandée, il n'y a rien à comparer : on sert la version qui existe.
		 */
		$courante = $this->config->lang;
		$demandee = is_object($courante) ? (string) $courante->info()->name : '';

		// Une table absente n'est pas une erreur : le module peut ne pas être installé. On rend la
		// langue demandée, et l'appelant se comporte exactement comme avant.
		if ($this->url->admin || !$this->db->table_exists($table))
		{
			return $demandee;
		}

		// `standalone()` : cette méthode est appelée depuis des modèles, parfois au milieu d'une
		// requête en cours de construction. Sans isolement, elle emporterait celle de l'appelant.
		$disponibles = $this->db->standalone(static function($db) use ($table, $colonne, $id){
			return $db	->select('lang')
						->from($table)
						->where($colonne, $id)
						->group_by('lang')
						->get();
		});

		$disponibles = array_values(array_filter(array_map('strval', (array) $disponibles)));

		// Aucune version, dans aucune langue : le contenu n'existe pas. Le 404 est alors la bonne
		// réponse, et ce n'est pas à cette méthode de la changer.
		if (!$disponibles)
		{
			return $demandee;
		}

		$this->declarer('langues_du_contenu', $disponibles);

		if (in_array($demandee, $disponibles, TRUE))
		{
			return $demandee;
		}

		/*
		 * L'ordre d'affichage des langues du site fait office d'ordre de préférence, ce qui rend le
		 * choix stable : deux visiteurs voient la même version, et un moteur de recherche aussi.
		 *
		 * `config->langs` est une liste NUMÉROTÉE d'addons, pas un tableau indexé par nom — on lit
		 * donc le nom sur chaque addon. La lire comme associative ne trouvait jamais rien.
		 */
		foreach ((array) $this->config->langs as $addon)
		{
			// Même prudence que plus haut : hors requête web, cette liste peut contenir autre chose
			// que des addons de langue. On passe, on ne fatalise pas.
			if (!is_object($addon))
			{
				continue;
			}

			$nom = (string) $addon->info()->name;

			if (in_array($nom, $disponibles, TRUE))
			{
				$this->declarer('langue_servie', $nom);

				return $nom;
			}
		}

		$this->declarer('langue_servie', $disponibles[0]);

		return $disponibles[0];
	}
}
