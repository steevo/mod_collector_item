/**
 * Module Collector : édition dans l'administration.
 * Adapte l'objet, le champ obligatoire et les balises à la collection choisie, insère les balises
 * dans le template, signale les balises inconnues et affiche un aperçu rendu par le composant.
 */
(() => {
	'use strict';

	const demarrer = () => {
		const options = (window.Joomla && Joomla.getOptions && Joomla.getOptions('mod_collector_item')) || {};
		const donnees = options.collections || {};
		const texte = cle => (window.Joomla && Joomla.Text ? Joomla.Text._(cle) : cle);

		const choixCollection = document.getElementById('jform_params_collection');
		const choixObjet      = document.getElementById('jform_params_item');
		const recherche       = document.getElementById('jform_params_item_search');
		const choixChamp      = document.getElementById('jform_params_required_field');
		const zoneBalises     = document.querySelector('[data-mod-collector-item-tags]');
		const aucuneBalise    = document.querySelector('[data-mod-collector-item-tags-empty]');
		const boutonDepart    = document.querySelector('[data-mod-collector-item-starter]');
		const alerte          = document.querySelector('[data-mod-collector-item-unknown]');
		const listeInconnues  = document.querySelector('[data-mod-collector-item-unknown-list]');
		const cadre           = document.querySelector('[data-mod-collector-item-frame]');
		const attente         = document.querySelector('[data-mod-collector-item-wait]');
		const autreObjet      = document.querySelector('[data-mod-collector-item-reroll]');

		if (!choixCollection) {
			return;
		}

		const collection = () => choixCollection.value;
		const infos = () => donnees[collection()] || { fields: [], tags: [] };

		const mode = () => {
			const coche = document.querySelector('input[name="jform[params][mode]"]:checked');
			return coche ? coche.value : 'random';
		};

		// valeur d'un éditeur (CodeMirror ou zone de texte simple)
		const editeur = id => window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances[id];
		const valeur = id => {
			const instance = editeur(id);
			if (instance && typeof instance.getValue === 'function') {
				return instance.getValue();
			}
			const champ = document.getElementById(id);
			return champ ? champ.value : '';
		};
		const remplacer = (id, contenu) => {
			const instance = editeur(id);
			if (instance && typeof instance.setValue === 'function') {
				instance.setValue(contenu);
			} else if (document.getElementById(id)) {
				document.getElementById(id).value = contenu;
			}
		};
		const inserer = contenu => {
			const instance = editeur('jform_params_content');
			if (instance && typeof instance.replaceSelection === 'function') {
				instance.replaceSelection(contenu);
				return;
			}
			const zone = document.getElementById('jform_params_content');
			if (zone) {
				const debut = zone.selectionStart || 0;
				zone.setRangeText(contenu, debut, zone.selectionEnd || debut, 'end');
				zone.focus();
			}
		};

		// --- objet affiché ---------------------------------------------------------------
		// objets cherchés par le composant (une collection peut en compter des milliers)
		const plusDObjets = document.querySelector('[data-mod-collector-item-more]');
		let objets = [];
		let rechercheEnCours = null;
		let minuteurRecherche = null;

		const remplirObjets = () => {
			if (!choixObjet) {
				return;
			}
			const actuel = choixObjet.value || choixObjet.dataset.value || '';
			const filtre = recherche ? recherche.value.trim() : '';

			choixObjet.innerHTML = '';
			choixObjet.append(new Option(texte(filtre !== '' && objets.length === 0 ? 'MOD_COLLECTOR_ITEM_ITEM_NO_MATCH' : 'MOD_COLLECTOR_ITEM_FIELD_ITEM_SELECT'), ''));

			objets.forEach(({ id, title, published }) => {
				const libelle = title + (published ? '' : ' ' + texte('MOD_COLLECTOR_ITEM_UNPUBLISHED_SUFFIX'));
				choixObjet.append(new Option(libelle, id, false, String(id) === actuel));
			});
		};

		const chercherObjets = () => {
			if (!choixObjet || !options.items) {
				return Promise.resolve();
			}
			if (rechercheEnCours) {
				rechercheEnCours.abort();
			}
			if (collection() === '') {
				objets = [];
				remplirObjets();
				return Promise.resolve();
			}
			rechercheEnCours = new AbortController();

			const adresse = new URL(options.items, window.location.href);
			adresse.searchParams.set('collection', collection());
			adresse.searchParams.set('search', recherche ? recherche.value.trim() : '');
			adresse.searchParams.set('id', choixObjet.value || choixObjet.dataset.value || '');
			if (options.token) {
				adresse.searchParams.set(options.token, '1');
			}

			return fetch(adresse, { signal: rechercheEnCours.signal, credentials: 'same-origin' })
				.then(reponse => reponse.json())
				.then(reponse => {
					objets = reponse.items || [];
					remplirObjets();
					if (plusDObjets) {
						plusDObjets.hidden = !reponse.more;
						plusDObjets.textContent = texte('MOD_COLLECTOR_ITEM_ITEM_MORE').replace('%d', reponse.total || '');
					}
				})
				.catch(() => {});
		};

		// --- champ obligatoire pour le tirage ---------------------------------------------
		const remplirChamps = () => {
			if (!choixChamp) {
				return;
			}
			const actuel = choixChamp.value || choixChamp.dataset.value || '';
			choixChamp.innerHTML = '';
			choixChamp.append(new Option(texte('MOD_COLLECTOR_ITEM_FIELD_REQUIRED_NONE'), ''));
			infos().fields.forEach(({ column, label }) => {
				choixChamp.append(new Option(label, column, false, column === actuel));
			});
		};

		// --- balises --------------------------------------------------------------------
		const afficherBalises = () => {
			if (!zoneBalises) {
				return;
			}
			zoneBalises.innerHTML = '';
			infos().tags.forEach(({ tag, label }) => {
				const bouton = document.createElement('button');
				bouton.type = 'button';
				bouton.className = 'btn btn-outline-secondary btn-sm';
				bouton.textContent = label;
				bouton.title = tag;
				bouton.addEventListener('click', () => inserer(tag));
				zoneBalises.append(bouton);
			});
			if (aucuneBalise) {
				aucuneBalise.hidden = collection() !== '';
			}
			if (boutonDepart) {
				boutonDepart.disabled = collection() === '';
			}
		};

		// modèle de départ : le premier champ en titre (lien vers la fiche), puis les autres champs
		const depart = () => {
			const noms = infos().fields.map(({ column }) => column);
			const [titre, ...autres] = noms;
			return '<div class="mod-collector-item-carte">\n'
				+ (titre ? `\t<h3 class="mod-collector-item-titre"><a href="{link}">{${titre}:text}</a></h3>\n` : '')
				+ autres.map(nom => `\t{if:${nom}}<div class="mod-collector-item-ligne"><span class="mod-collector-item-label">{${nom}:label}</span> {${nom}}</div>{/if}`).join('\n')
				+ '\n\t<p class="mod-collector-item-plus"><a href="{link}">…</a></p>\n</div>\n';
		};

		if (boutonDepart) {
			boutonDepart.addEventListener('click', () => {
				if (valeur('jform_params_content').trim() !== '' && !window.confirm(texte('MOD_COLLECTOR_ITEM_STARTER_CONFIRM'))) {
					return;
				}
				remplacer('jform_params_content', depart());
			});
		}

		// balises du contenu qui ne correspondent à rien dans la collection (même syntaxe que le rendu)
		let dernierTexte = null;
		const verifier = (force = false) => {
			if (!alerte) {
				return;
			}
			const contenu = valeur('jform_params_content');
			if (!force && contenu === dernierTexte) {
				return;
			}
			dernierTexte = contenu;

			const champs = new Set(infos().fields.map(({ column }) => column));
			const reserves = ['link', 'id', 'hits'];
			const inconnues = new Set();

			for (const [balise, condition, nom] of contenu.matchAll(/\{(?:(if|ifnot):)?([a-z0-9_]+)\}/g)) {
				const connu = champs.has(nom) || reserves.includes(nom) || (!condition && nom === 'else');
				if (!connu) {
					inconnues.add(balise);
				}
			}
			for (const [balise, nom] of contenu.matchAll(/\{([a-z0-9_]+):(?:label|value|text|url)\}/g)) {
				if (!champs.has(nom)) {
					inconnues.add(balise);
				}
			}

			listeInconnues.innerHTML = '';
			[...inconnues].forEach((balise, i) => {
				if (i) {
					listeInconnues.append(' ');
				}
				const code = document.createElement('code');
				code.textContent = balise;
				listeInconnues.append(code);
			});
			alerte.hidden = inconnues.size === 0 || collection() === '';
		};

		// --- aperçu -----------------------------------------------------------------------
		let objetTire = '';
		const tirer = () => {
			const publies = objets.filter(({ published }) => published);
			objetTire = publies.length ? String(publies[Math.floor(Math.random() * publies.length)].id) : '';
		};
		// mode « le plus récent » : l'objet retenu par le critère choisi, calculé par le serveur
		const choixRecent = document.getElementById('jform_params_latest_by');
		const objetRecent = () => {
			const plusRecents = infos().latest || {};
			const id = plusRecents[choixRecent ? choixRecent.value : 'ordering'];
			return id ? String(id) : '';
		};
		const objetApercu = () => {
			if (mode() === 'item') {
				return choixObjet ? choixObjet.value : '';
			}
			return mode() === 'latest' ? objetRecent() : objetTire;
		};

		const ajusterCadre = () => {
			try {
				const doc = cadre.contentDocument;
				if (doc && doc.documentElement) {
					cadre.style.height = Math.min(Math.max(doc.documentElement.scrollHeight, 150), 1200) + 'px';
				}
			} catch (e) {
				// cadre inaccessible : on garde sa hauteur
			}
		};

		let envoye = null;
		let minuteur = null;
		let requete = null;

		const apercu = (force = false) => {
			if (!cadre || !options.preview) {
				return;
			}
			const demande = {
				content: valeur('jform_params_content'),
				css: valeur('jform_params_css'),
				collection: collection(),
				item: objetApercu(),
			};
			const cle = JSON.stringify(demande);
			if (!force && cle === envoye) {
				return;
			}
			envoye = cle;

			if (autreObjet) {
				autreObjet.hidden = mode() !== 'random' || collection() === '';
			}

			clearTimeout(minuteur);
			minuteur = setTimeout(() => {
				if (demande.collection === '') {
					cadre.srcdoc = '';
					return;
				}
				if (requete) {
					requete.abort();
				}
				requete = new AbortController();

				const corps = new FormData();
				// sans template, le module affiche le titre de l'objet avec un lien
				let contenu = demande.content;
				if (contenu.trim() === '') {
					const objet = objets.find(({ id }) => String(id) === demande.item) || objets[0];
					const titre = document.createElement('span');
					titre.textContent = objet ? objet.title : '';
					contenu = '<p class="mod-collector-item-title"><a href="{link}">' + titre.innerHTML + '</a></p>';
				}
				corps.append('jform[content]', contenu);
				corps.append('jform[css]', demande.css);
				corps.append('jform[collection]', demande.collection);
				corps.append('jform[client]', '0');
				corps.append('jform[id]', '0');
				corps.append('item', demande.item);
				if (options.token) {
					corps.append(options.token, '1');
				}

				attente.hidden = false;
				fetch(options.preview, { method: 'POST', body: corps, signal: requete.signal, credentials: 'same-origin' })
					.then(reponse => reponse.text())
					.then(html => {
						cadre.srcdoc = html;
						attente.hidden = true;
					})
					.catch(erreur => {
						if (erreur.name !== 'AbortError') {
							attente.hidden = true;
						}
					});
			}, 400);
		};

		if (cadre) {
			cadre.addEventListener('load', ajusterCadre);
		}
		if (autreObjet) {
			autreObjet.addEventListener('click', () => {
				tirer();
				apercu(true);
			});
		}

		// --- liaisons ---------------------------------------------------------------------
		const actualiser = () => {
			remplirChamps();
			afficherBalises();
			verifier(true);
			chercherObjets().then(() => {
				tirer();
				apercu(true);
			});
		};

		choixCollection.addEventListener('change', () => {
			if (choixObjet) {
				choixObjet.dataset.value = '';
				choixObjet.value = '';
			}
			if (choixChamp) {
				choixChamp.dataset.value = '';
				choixChamp.value = '';
			}
			actualiser();
		});
		if (recherche) {
			recherche.addEventListener('input', () => {
				clearTimeout(minuteurRecherche);
				minuteurRecherche = setTimeout(chercherObjets, 300);
			});
			// Entrée dans la recherche ne doit pas enregistrer le module
			recherche.addEventListener('keydown', event => {
				if (event.key === 'Enter') {
					event.preventDefault();
				}
			});
		}
		if (choixObjet) {
			choixObjet.addEventListener('change', () => apercu());
		}
		document.querySelectorAll('input[name="jform[params][mode]"]').forEach(radio => radio.addEventListener('change', () => apercu()));
		if (choixRecent) {
			choixRecent.addEventListener('change', () => apercu());
		}

		actualiser();

		// l'éditeur (CodeMirror ou zone de texte) n'a pas d'événement commun : on relit le contenu régulièrement
		window.addEventListener('load', () => {
			verifier(true);
			apercu(true);
		});
		setInterval(() => {
			verifier();
			apercu();
		}, 400);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', demarrer);
	} else {
		demarrer();
	}
})();
