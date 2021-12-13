# Vitemadose API
## _Documentation_
URL = https://api.vitemado.se
L'API Vitemadose permet d'accéder aux derniers créneaux présentés sur le site http://vitemadose.covidtracker.fr
Il s'agit d'un API json.

## Fonctionnalités

- Récupère les disponibilités des créneaux de vaccination des plateformes référencées par Vitemadose, pour un département donné.
- Possibilité de filtrer par plateforme.
- possibilité de filtrer par type de vaccins proposés par le centre.
- Possibilité de filtrer par motif (dose de rappel uniquement par exemple)
- Possibilité de configurer une date maximale pour les créneaux.

## Paramètres GET

L'API propose un certain nombre de paramètres. Le seul paramètre OBLIGATOIRE est **department** et prend comme valeur le numéro du département. Le contenu des champs n'est pas case_sensitive.
| Paramètre GET |  Action | Type | Oobligatoire |
| ------ | ------ |------ |------ |
| department | renvoie uniquement les centres du département | integer | **oui**
| vaccine[] | renvoie uniquement les centres proposant un des vaccins renseignés | array[string] | non
| platform[] | renvoie uniquement les centres pour les plateformes renseignées | array[string] | non
| maxDate | renvoie uniquement les centres ayant des dispos avant la date renseignée | YYY-mm-dd | non (renvoie alors l'ensemble des disponibilités scrapées)
| vaccinationMotive | renvoie uniquement les centres ayant des dispos pour le motif renseigné | string | non (renvoie alors l'ensemble des centres ayant minCreneauxCount pour le motigf "all")
| minCreneauxCount | renvoie les centres ayant au moins minCreneauxCountdispos pour le motif renseigné (motif "all" si aucun motif renseigné)| integer | non (renvoie alors les centres disponibles ou indisponibles)
- "vaccine[]" - valeurs acceptées
```
pfizer-biontech
moderna
arnm (attention, n'inclut pas automatiquement pfizer et moderna)
astrazeneca
janssen   
```

- "platform[]" - valeurs acceptées
```
doctolib
keldoc
maiia
mesoigner
avecmondoc
bimedoc
mapharma
ordoclic
valwin
```

## Exemples

http://api.vitemado.se?department=75&vaccine[]=pfizer-biontech&maxDate=2022-01-20
