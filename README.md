# mod_collector_item

Module de site Joomla 5/6 pour le composant [Collector](https://github.com/steevo/collector) : affiche un objet d'une collection.

- **Objet choisi** : un objet précis, sélectionné avec une recherche par titre ou par numéro.
- **Au hasard** : un nouveau tirage à chaque affichage, ou le même objet pour tous les visiteurs pendant une heure, un jour ou une semaine. Le tirage peut se limiter aux objets dont un champ est rempli (par exemple une photo).
- **Affichage** : un template HTML et du CSS, avec les mêmes balises que les templates du composant (`{champ}`, `{champ:label}`, `{link}`, `{if:champ}…{/if}`…), un modèle de départ et un aperçu en direct.

Le module ne s'affiche pas si la collection ou l'objet n'est pas visible par le visiteur (publication, dates, niveau d'accès).

Nécessite com_collector avec la tâche `template.items` (recherche d'objets dans l'administration).
