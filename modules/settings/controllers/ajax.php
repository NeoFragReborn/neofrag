<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 *
 * Le plan du site ne lit plus les tables des autres modules : chacun donne ses adresses par le
 * carrefour `sitemap` (2026-10-03). Un module absent ne contribue pas, et ne peut donc
 * plus faire tomber /sitemap.xml.
 */

namespace NF\Modules\Settings\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function humans()
	{
		return $this->config->nf_humans_txt;
	}

	public function favicon()
	{
		// Même résolution que le gabarit principal, et la MÊME fonction : le favicon configuré s'il
		// existe, sinon celui du cœur.
		$favicon = favicon_url();

		// Une VRAIE redirection HTTP, pas Url::redirect() : sur une route « ajax/… » celui-ci répond un JSON
		// {"redirect": …} qu'un navigateur en quête d'icône ne lira jamais.
		header('Location: '.$favicon, TRUE, 302);
		header('Cache-Control: public, max-age=86400');
		exit;
	}

	/**
	 * `robots.txt` : le texte des Préférences générales, et l'adresse COMPLÈTE du plan du site — la norme
	 * l'exige, et `Sitemap: /fr/sitemap.xml` n'était pas lu (2026-10-03). Le plan annoncé est
	 * l'index des langues, à la racine. Une ligne `Sitemap:` relative saisie à la main est complétée.
	 */
	public function robots()
	{
		$content = rtrim((string) $this->config->nf_robots_txt);
		$origine = site_origin();

		$content = (string) preg_replace_callback('/^(\s*sitemap\s*:\s*)(\/\S*)\s*$/mi', static fn (array $m): string => $m[1].$origine.$m[2], $content);

		if (stripos($content, 'sitemap:') === FALSE)
		{
			$content .= "\n\nSitemap: ".nf_seo_adresse($origine, $this->url->base, '', 'sitemap.xml');
		}

		return $content."\n";
	}

	/**
	 * Un nom court qui ne coupe pas un mot en deux.
	 *
	 * C'est ce qui s'affiche sous l'icône sur un écran d'accueil, où la place manque. Une coupe
	 * brute au douzième caractère donnait « NeoFrag Rebo » — on préfère « NeoFrag ».
	 */
	private static function nom_court(string $nom): string
	{
		if (mb_strlen($nom) <= 12)
		{
			return $nom;
		}

		$mots  = preg_split('/\s+/', $nom) ?: [$nom];
		$court = '';

		foreach ($mots as $mot)
		{
			if ($court !== '' && mb_strlen($court.' '.$mot) > 12)
			{
				break;
			}

			$court = $court === '' ? $mot : $court.' '.$mot;
		}

		// Un premier mot déjà trop long : là, on coupe, faute de mieux.
		return mb_substr($court ?: $nom, 0, 12);
	}

	/**
	 * Le manifeste d'application : ce qui rend le site installable.
	 *
	 * « Ajouter à l'écran d'accueil » sur téléphone, une fenêtre propre sur ordinateur. Rien ici ne
	 * peut casser une page : un navigateur qui ne comprend pas le manifeste l'ignore.
	 *
	 * Il est produit à la demande plutôt que déposé en fichier, parce que tout son contenu dépend
	 * du site : son nom, sa couleur, son adresse de départ, et le favicon que l'administrateur a
	 * choisi. Un fichier figé serait faux partout ailleurs que sur l'installation qui l'a écrit.
	 */
	/**
	 * `/service-worker.js` — le seul composant du produit qui survit à son propre retrait.
	 *
	 * POURQUOI IL EST TRAITÉ AUTREMENT QUE TOUT LE RESTE. Un service worker installé dans un
	 * navigateur y reste, et continue de répondre à la place du réseau, MÊME si le fichier
	 * disparaît du serveur. Supprimer le fichier ne le désinstalle pas : cela laisse en place la
	 * dernière version connue, pour toujours, chez des visiteurs qu'on ne peut pas joindre. C'est
	 * la seule façon dont ce produit peut casser un site sans qu'aucun déploiement ne le répare.
	 *
	 * D'où la forme retenue : cette adresse répond TOUJOURS, et ce qu'elle rend dépend du réglage.
	 * Réglage actif, elle rend un worker qui sert ; réglage éteint, elle rend un worker qui EFFACE
	 * ses caches, se désinscrit et recharge les onglets ouverts. Éteindre l'interrupteur désinstalle
	 * donc réellement, parce que les navigateurs revérifient le script à chaque navigation.
	 *
	 * CE QU'IL NE MET JAMAIS EN CACHE : le HTML. Une page mise en cache survit à un déploiement, et
	 * l'on se retrouve à servir un site d'avant-hier à qui l'a visité, sans moyen de s'en apercevoir.
	 * Les navigations passent au réseau, toujours. Seuls les fichiers statiques sont gardés, et ils
	 * portent déjà `?v=<date de modification>` dans leur adresse : une version neuve est une autre
	 * adresse, donc jamais masquée par l'ancienne.
	 */
	public function service_worker()
	{
		if (!$this->config->nf_pwa)
		{
			return <<<'JS'
/* NeoFrag Reborn — service worker DÉSACTIVÉ. Ce fichier ne sert rien : il se retire.
   Il reste servi, et c'est le but : c'est en le récupérant qu'un worker déjà installé apprend
   qu'il doit disparaître. Aucun écouteur `fetch` ici — il ne répond à la place de rien. */
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
    e.waitUntil((async function () {
        for (const nom of await caches.keys()) {
            await caches.delete(nom);
        }

        await self.registration.unregister();

        // Recharger les onglets ouverts : sans cela, celui qui lit la page continue d'être servi
        // par le worker sortant jusqu'à sa prochaine navigation.
        for (const client of await self.clients.matchAll({ type: 'window' })) {
            client.navigate(client.url);
        }
    })());
});
JS;
		}

		/*
		 * Le nom du cache porte une version. Elle ne sert pas à invalider les fichiers — leur
		 * adresse porte déjà leur date de modification — mais à balayer les caches d'une version
		 * précédente, qui ne seraient plus jamais lus.
		 */
		$version = (string) NEOFRAG_VERSION.'-'.(int) $this->config->nf_theme_epoch;

		$js = <<<'JS'
/* NeoFrag Reborn — service worker.
   Il garde les fichiers STATIQUES, jamais le HTML : une page en cache survivrait à un déploiement.
   Se désinstalle en éteignant « Application installable » dans Administration → Paramètres. */
const NF_CACHE = 'nf-statiques-__VERSION__';

/* Ce qu'on garde : des fichiers dont l'adresse porte déjà la date de modification. */
const NF_STATIQUE = /\.(?:css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot)$/i;

self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
    e.waitUntil((async function () {
        for (const nom of await caches.keys()) {
            if (nom !== NF_CACHE) {
                await caches.delete(nom);
            }
        }

        await self.clients.claim();
    })());
});

self.addEventListener('fetch', function (e) {
    const requete = e.request;

    if (requete.method !== 'GET') {
        return;
    }

    const adresse = new URL(requete.url);

    /* Jamais un autre domaine : ce n'est pas à nous de garder ce que sert quelqu'un d'autre. */
    if (adresse.origin !== self.location.origin) {
        return;
    }

    /* JAMAIS le HTML. Une navigation part au réseau, toujours — deux fois plutôt qu'une, parce
       qu'un `accept` sans `mode` arrive aussi de certains navigateurs. */
    if (requete.mode === 'navigate' || (requete.headers.get('accept') || '').indexOf('text/html') !== -1) {
        return;
    }

    if (!NF_STATIQUE.test(adresse.pathname)) {
        return;
    }

    e.respondWith((async function () {
        const cache = await caches.open(NF_CACHE);
        const connu = await cache.match(requete);

        if (connu) {
            return connu;
        }

        const reponse = await fetch(requete);

        /* `basic` : une réponse de notre propre origine, lisible. On ne garde ni les erreurs, ni
           les réponses opaques, qui masqueraient un échec derrière un succès apparent. */
        if (reponse && reponse.status === 200 && reponse.type === 'basic') {
            cache.put(requete, reponse.clone());
        }

        return reponse;
    })());
});
JS;

		return str_replace('__VERSION__', preg_replace('/[^A-Za-z0-9._-]/', '', $version), $js);
	}

	public function manifest()
	{
		$nom = trim((string) $this->config->nf_name) ?: 'NeoFrag';

		/*
		 * Les icônes. On ne déclare que des tailles VRAIES : un navigateur vérifie, et une taille
		 * annoncée à tort fait rejeter l'icône — donc le manifeste entier pour l'installabilité.
		 *
		 * `maskable` sur la grande : elle occupe tout le carré, ce qui permet aux systèmes qui
		 * découpent en cercle ou en goutte de ne pas rogner un logo centré.
		 */
		// `image()` et non `url()` : ce dernier ajoute le préfixe de langue, et une icône n'a pas de
		// version par langue. Le manifeste étant unique pour le site, ses icônes doivent l'être.
		$icones = [
			[
				'src'   => image('branding/icon-reborn-dark.png'),
				'sizes' => '1254x1254',
				'type'  => 'image/png',
				'purpose' => 'any maskable',
			],
			[
				'src'   => image('apple-touch-icon.png'),
				'sizes' => '180x180',
				'type'  => 'image/png',
			],
		];

		// Le favicon choisi par l'administrateur passe devant : c'est l'identité de SON site.
		// `model2()` est un chargeur générique : PHPStan ne sait pas qu'il rend ici le modèle File.
		/** @var \NF\NeoFrag\Models\File|null $fichier */
		$fichier = $this->config->nf_favicon ? $this->model2('file', $this->config->nf_favicon) : NULL;

		if ($fichier && ($chemin = $fichier->path()))
		{
			// Sans `sizes` : on ne connaît pas les dimensions de ce que l'administrateur a
			// téléversé, et annoncer une taille fausse fait rejeter l'icône — voire le manifeste.
			array_unshift($icones, [
				'src'  => $this->url->base.ltrim($chemin, '/'),
				'type' => get_mime_by_extension(extension($chemin)),
			]);
		}

		$couleur = trim((string) $this->config->nf_theme_color);

		return json_encode(array_filter([
			'name'             => $nom,
			// Le nom court s'affiche sous l'icône, où la place manque.
			'short_name'       => self::nom_court($nom),
			'description'      => trim((string) $this->config->nf_description) ?: NULL,
			'start_url'        => url(),
			'scope'            => url(),
			'display'          => 'standalone',
			'orientation'      => 'any',
			'lang'             => $this->config->lang->info()->name,
			'dir'              => 'ltr',
			'theme_color'      => $couleur ?: NULL,
			'background_color' => $couleur ?: NULL,
			'icons'            => $icones,
		]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}

	/**
	 * Le plan du site (2026-10-03).
	 *
	 * À la racine (`/sitemap.xml`, l'adresse qu'annonce `robots.txt`), un site en plusieurs langues sert
	 * l'INDEX de ses plans, un par langue ; `/fr/sitemap.xml` est le plan du français. Jusque-là, la racine
	 * servait le plan d'une seule langue — celle que préférait le navigateur —, aux adresses relatives,
	 * que Google ignore, et d'une liste de modules écrite ici en dur : ni le wiki, ni les sujets du forum.
	 *
	 * Chaque module donne désormais ses adresses lui-même : c'est le carrefour `sitemap`
	 * (`modules/<module>/controllers/sitemap.php`, méthode `sitemap()`, contrat tenu par
	 * `check-addon-contracts`), que réunit nf_seo_plan() (helpers/seo.php). Il rend des chemins comme ceux
	 * que prend `url()` — `articles/12/titre` devient `/fr/blog/12/titre` —, ne garde que ce qu'un VISITEUR
	 * peut lire, et seulement ce qui existe dans la langue du plan : un contenu servi dans une autre langue
	 * se déclare canonique ailleurs.
	 */
	public function sitemap()
	{
		$origine = site_origin();
		$demande = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

		if (count($this->config->langs) > 1 && $demande === rtrim($this->url->base, '/').'/sitemap.xml')
		{
			return nf_seo_index_xml(array_map(fn ($langue): string => nf_seo_adresse($origine, $this->url->base, $langue->info()->name, 'sitemap.xml'), $this->config->langs));
		}

		return nf_seo_plan_xml(nf_seo_plan()['adresses']);
	}

	/**
	 * La clé IndexNow, à la racine : `/<clé>.txt` ne contient qu'elle. Le moteur la lit pour
	 * s'assurer qu'un envoi vient bien du site (nf_indexnow()). Toute autre adresse de cette forme, et la
	 * clé elle-même quand IndexNow est éteint, répondent 404 (Ajax_Checker::indexnow()).
	 */
	public function indexnow()
	{
		return nf_indexnow_cle();
	}

	public function debug_bar()
	{
		if ($tab = post('tab'))
		{
			$this->session->set('debug', 'tab', $tab);
		}
		else if ($tab === '')
		{
			$this->session->destroy('debug', 'tab');
		}
		else if ($height = (int)post('height'))
		{
			$this->session->set('debug', 'height', $height);
		}
	}

	public function languages()
	{
		if (post())
		{
			foreach ($this->config->langs as $language)
			{
				if (($name = $language->info()->name) == post('language'))
				{
					if ($this->user->id)
					{
						$this->user->set('language', $language->__addon)->update();
					}
					else
					{
						$this->session->set('language', $language->info()->name);
					}

					if ($name != $this->config->lang->info()->name)
					{
						$cible = $this->url->base.$name.substr(post('url'), strlen($this->url->base.$this->config->lang->info()->name));

						/*
						 * DEUX APPELANTS, DEUX RÉPONSES, et c'est ce que le code ne distinguait pas.
						 *
						 * La modale « Choisir ma langue » appelle cette route en `fetch()` et attend
						 * un JSON `{redirect: …}` : `modules/settings/js/languages.js` lit `data.redirect`.
						 *
						 * Mais les thèmes `nebula` et `forge` portent, eux, un vrai FORMULAIRE dans leur
						 * pied de page (`form.fg-lang`), que rien n'intercepte. Il s'envoyait donc
						 * nativement, et comme l'adresse contient le segment `ajax`, `redirect()`
						 * répondait du JSON avec un code 200 : le visiteur qui changeait de langue
						 * atterrissait sur `{"redirect":"\/fr\/…"}` affiché en texte brut, sur le
						 * thème par défaut du produit. Mesuré dans un vrai navigateur le 2026-09-22,
						 * par `tests/E2E/langue.spec.js`.
						 *
						 * L'en-tête `X-Requested-With` sépare les deux — c'est l'emploi que la fiche
						 * A17 lui cherchait, en notant qu'il était calculé et lu nulle part.
						 */
						if ($this->url->ajax_header)
						{
							$this->url->redirect($cible);
						}

						$this->url->redirect_http($cible);
					}

					break;
				}
			}
		}
		else
		{
			return $this->js('languages')
						->modal('Choisir ma langue', 'fas fa-globe')
						->body($this->view('languages'))
						->cancel();
		}
	}
}
