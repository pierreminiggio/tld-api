# tld-api

Valide l'existence d'un TLD (Top-Level Domain) à partir d'une copie locale, mise en cache en base, de la
[liste officielle des TLD de l'IANA](https://data.iana.org/TLD/tlds-alpha-by-domain.txt).

## Endpoint

```
GET /{tld}
```

`{tld}` peut être donné en ASCII (ex. `com`, `xn--p1ai`) ou directement dans son script d'origine
(ex. `рф`, `澳門`, `한국`) — il est automatiquement converti vers sa forme punycode canonique avant
d'être vérifié. La conversion est faite en PHP pur (implémentation de l'algorithme Punycode, RFC 3492),
sans dépendre de l'extension `intl`.

Réponse JSON dans tous les cas. `tld` est toujours la forme ASCII (punycode) canonique :

```json
{"tld": "com", "valid": true}
{"tld": "xn--mix891f", "valid": true}
```

| Code HTTP | Signification |
|---|---|
| 200 | Le TLD existe dans la liste mise en cache |
| 404 | Le TLD n'existe pas, ou n'a pas un format de TLD valide |

`GET /` redirige (302) vers `/openapi`, qui affiche une page HTML expliquant l'utilisation de l'API.

## Commande de mise à jour de la liste

```
php command/update-tlds.php
```

Télécharge la liste IANA, et **ajoute uniquement les nouveaux TLD** manquants en base — les TLD déjà
enregistrés ne sont jamais supprimés ni modifiés (pas de suppression de TLD malgré leur éventuel retrait
côté IANA). Le script utilise des chemins basés sur `__DIR__`, donc il peut être appelé avec un chemin
absolu depuis l'extérieur du projet (ex. dans un cron).

## Migration

```sql
CREATE TABLE `tld_list` (
  `id` int(11) NOT NULL,
  `tld` varchar(63) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `tld_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tld` (`tld`);

ALTER TABLE `tld_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
```

La contrainte unique sur `tld` sert de filet de sécurité contre les doublons (en plus du filtrage fait en
PHP avant l'insertion par la commande de mise à jour).

## Tests

```
composer install
vendor/bin/phpunit
```
