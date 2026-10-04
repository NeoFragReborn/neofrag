#!/bin/sh
# Adapte les configurations LIVRÉES (`nginx.conf`, `Caddyfile`) au banc des serveurs web, exactement là
# où elles disent « à adapter », et nulle part ailleurs : l'adresse de PHP-FPM (le service `php` du banc
# au lieu d'une socket), et pour Caddy l'adresse du site (`:80`, sans certificat, au lieu d'un domaine).
# Le dossier du site est déjà le bon (/var/www/neofrag). Tout le reste — les refus, la réécriture, les
# en-têtes — est éprouvé tel que livré.
#
#   sh .github/serveurs-web/adapter.sh <racine du site> <dossier de sortie>
set -eu

racine=$1
sortie=$2
mkdir -p "$sortie"

remplacer() { # fichier motif remplacement — échoue si le motif n'y est pas : la livraison a changé
    grep -qF "$2" "$1" || { echo "adapter.sh : « $2 » introuvable dans $1 — l'exemple livré a changé" >&2; exit 1; }
    sed -i "s#$(printf '%s' "$2" | sed 's/[.[\*^$#]/\\&/g')#$3#" "$1"
}

# nginx : le serveur tel quel ; le fragment FastCGI est celui que l'exemple donne en commentaire.
cp "$racine/nginx.conf" "$sortie/nginx.conf"
sed -n '/neofrag-fastcgi.conf :/,$p' "$racine/nginx.conf" | sed -n 's/^#\t//p' > "$sortie/neofrag-fastcgi.conf"
remplacer "$sortie/neofrag-fastcgi.conf" 'fastcgi_pass unix:/run/php/php-fpm.sock;' 'fastcgi_pass php:9000;'

# Caddy
cp "$racine/Caddyfile" "$sortie/Caddyfile"
remplacer "$sortie/Caddyfile" 'example.org {' ':80 {'
remplacer "$sortie/Caddyfile" 'php_fastcgi unix//run/php/php-fpm.sock' 'php_fastcgi php:9000'

echo "configurations adaptées dans $sortie :"
grep -n 'fastcgi_pass' "$sortie/neofrag-fastcgi.conf"
grep -n ':80 {\|php_fastcgi' "$sortie/Caddyfile"
