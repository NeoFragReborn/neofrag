<?php
/**
 * Google Analytics, sous CONSENTEMENT.
 *
 * La bannière de cookies pose `nf_consent` : « full » (tout accepter) ou « essentials » (refuser le
 * non essentiel). Jusqu'au 2026-09-17, ce gabarit chargeait Google dès que `nf_analytics` était
 * renseigné, sans regarder ce cookie — la bannière demandait un accord qui n'était pas attendu.
 *
 *   - consentement complet déjà donné : le chargeur de Google est émis directement ;
 *   - sinon : un petit script attend l'événement `nf:consent` que la bannière émet au clic, et ne
 *     charge Google que si le niveau est « full ». Rien ne part avant.
 *
 * Les <script> inline reçoivent leur nonce du filtre d'index.php ; le chargeur externe le porte aussi,
 * et index.php n'ajoute googletagmanager.com à `script-src` que si un identifiant est configuré.
 */
$id = (string) $this->config->nf_analytics;

if ($id === '')
{
	return;
}

if (($_COOKIE['nf_consent'] ?? '') === 'full'):
?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo rawurlencode($id) ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', <?php echo json_encode($id) ?>);
</script>
<?php else: ?>
<script src="<?php echo path('analytics-consent.js', 'js') ?>?v=<?php echo asset_version('analytics-consent.js', 'js') ?: (int) $this->config->nf_version_css ?>" data-analytics-id="<?php echo nf_texte($id) ?>"></script>
<?php endif;
