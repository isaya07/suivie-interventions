# Optimiseur de Planning d'Interventions

## Vue d'ensemble

L'optimiseur de planning est un système avancé qui planifie automatiquement les interventions en tenant compte de multiples facteurs :

- **Localisation géographique** et temps de trajet entre interventions
- **Durée estimée** de chaque intervention
- **Délais et priorités** des interventions
- **Disponibilité des techniciens** et leurs zones de couverture
- **Coûts de déplacement** et contraintes de distance
- **Fenêtres horaires** et contraintes de planning

## Fonctionnalités principales

### 🧠 Algorithme d'optimisation
- **Algorithme génétique** pour trouver la solution optimale
- **Optimisation multi-critères** (temps, distance, priorité, coût)
- **Paramètres configurables** pour différents profils d'optimisation
- **Score de qualité** pour évaluer les solutions

### 🗺️ Gestion géographique
- **Géocodage automatique** des adresses avec OpenStreetMap
- **Calcul des temps de trajet** entre localisations
- **Zones d'intervention** pour organiser les techniciens
- **Cache des trajets** pour optimiser les performances

### 👥 Gestion des ressources
- **Disponibilités des techniciens** avec créneaux personnalisés
- **Zones de couverture** par technicien
- **Spécialités** et compétences techniques
- **Coûts de déplacement** variables

### 📊 Analyse et reporting
- **Statistiques de planning** (temps, distance, coût)
- **Scores d'optimisation** pour comparer les solutions
- **Historique des plannings** générés
- **Export et visualisation** des résultats

## Installation

### 1. Base de données

Exécutez le script d'installation pour créer les tables nécessaires :

```bash
php backend/scripts/install_optimizer.php
```

### 2. Configuration

L'optimiseur utilise les paramètres suivants (configurables via l'interface) :

- **Poids distance** (30%) : Importance de minimiser les déplacements
- **Poids temps** (25%) : Importance de minimiser la durée totale
- **Poids priorité** (25%) : Importance de respecter les priorités
- **Poids coût** (20%) : Importance de minimiser les coûts

### 3. Données de base

Pour utiliser l'optimiseur, vous devez avoir :

- **Clients avec adresses** géolocalisées
- **Techniciens** avec zones d'intervention assignées
- **Interventions** avec localisations et durées estimées

## Utilisation

### Interface Web

Accédez à l'optimiseur via : `http://localhost:3002/planning/optimizer`

#### 1. Configuration de l'optimisation

- **Période** : Sélectionnez les dates de début et fin
- **Profil d'optimisation** : Choisissez parmi les profils prédéfinis
- **Paramètres d'algorithme** :
  - Taille de population : 10-200 (par défaut 50)
  - Générations : 50-500 (par défaut 100)
  - Taux de mutation : 0.01-0.5 (par défaut 0.1)

#### 2. Lancement de l'optimisation

1. Cliquez sur **"Optimiser le planning"**
2. L'algorithme trouve la meilleure solution
3. Visualisez les résultats par technicien
4. Analysez les statistiques (temps, distance, coût)

#### 3. Application du planning

1. Vérifiez le planning proposé
2. Cliquez sur **"Appliquer ce planning"**
3. Les interventions sont automatiquement reprogrammées

### API

#### Optimiser un planning

```http
POST /api/planning_optimizer.php?action=optimiser
Content-Type: application/json

{
  "date_debut": "2024-01-15",
  "date_fin": "2024-01-22",
  "parametres_id": 1,
  "population_size": 50,
  "generations": 100,
  "mutation_rate": 0.1
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "planning_id": 123,
    "planning": [...],
    "score": 8.75,
    "temps_calcul_ms": 2500,
    "statistiques": {
      "nombre_interventions": 15,
      "nombre_techniciens": 3,
      "duree_totale_estimee": 1800,
      "distance_totale_estimee": 85.5
    }
  }
}
```

#### Appliquer un planning

```http
POST /api/planning_optimizer.php?action=appliquer
Content-Type: application/json

{
  "planning_id": 123
}
```

#### Récupérer les paramètres

```http
GET /api/planning_optimizer.php?action=parametres
```

## Configuration avancée

### Profils d'optimisation

Quatre profils prédéfinis sont disponibles :

1. **Optimisation équilibrée** (par défaut)
   - Distance: 30%, Temps: 25%, Priorité: 25%, Coût: 20%

2. **Priorité temps**
   - Distance: 20%, Temps: 40%, Priorité: 25%, Coût: 15%

3. **Priorité coût**
   - Distance: 40%, Temps: 20%, Priorité: 15%, Coût: 25%

4. **Priorité urgence**
   - Distance: 15%, Temps: 20%, Priorité: 50%, Coût: 15%

### Zones d'intervention

Définissez des zones géographiques pour organiser vos techniciens :

```sql
INSERT INTO technicien_zones (technicien_id, zone_intervention, rayon_action, cout_deplacement_km)
VALUES (1, 'Paris-IDF', 50, 0.50);
```

### Disponibilités

Gérez les créneaux de disponibilité :

```sql
INSERT INTO disponibilites_technicien (technicien_id, date_debut, date_fin, type_creneau)
VALUES (1, '2024-01-15 08:00:00', '2024-01-15 18:00:00', 'travail');
```

## Algorithme d'optimisation

### Algorithme génétique

L'optimiseur utilise un algorithme génétique qui :

1. **Génère une population initiale** de solutions aléatoires
2. **Évalue la fitness** de chaque solution selon les critères
3. **Sélectionne les meilleures solutions** (sélection par tournoi)
4. **Croise** les solutions pour créer de nouvelles variantes
5. **Mute** aléatoirement certaines solutions
6. **Répète** le processus sur plusieurs générations
7. **Retourne la meilleure solution** trouvée

### Fonction de fitness

Le score d'une solution prend en compte :

- **Temps total** des interventions et trajets
- **Distance totale** parcourue
- **Respect des priorités** (interventions urgentes en premier)
- **Coûts de déplacement** estimés
- **Pénalités** pour dépassement des contraintes

### Contraintes

- **Durée maximale** par journée de travail (8h par défaut)
- **Distance maximale** acceptable pour un trajet (100km)
- **Fenêtres horaires** des interventions
- **Disponibilités** des techniciens

## Performance

### Optimisations

- **Cache des trajets** : Les temps de trajet sont mis en cache
- **Géocodage** : Les coordonnées sont sauvegardées
- **Index de base de données** : Optimisation des requêtes
- **Parallélisation** : Possible sur plusieurs cœurs

### Temps de calcul

- **10-20 interventions** : < 1 seconde
- **20-50 interventions** : 1-5 secondes
- **50-100 interventions** : 5-30 secondes
- **100+ interventions** : 30+ secondes

### Recommandations

- Utilisez des **populations plus petites** pour des calculs rapides
- Augmentez les **générations** pour de meilleurs résultats
- Ajustez le **taux de mutation** selon la complexité

## Monitoring et debug

### Logs

Les logs sont disponibles dans :
- Logs d'API : `backend/logs/planning_optimizer.log`
- Logs de géocodage : `backend/logs/geocoding.log`

### Métriques

Surveillez :
- **Temps de calcul** des optimisations
- **Score de qualité** des plannings
- **Taux d'application** des plannings générés
- **Erreurs de géocodage**

### Débogage

En cas de problème :

1. Vérifiez que toutes les **localisations sont géocodées**
2. Contrôlez les **disponibilités des techniciens**
3. Validez les **zones d'intervention** assignées
4. Analysez les **logs d'erreur**

## Intégration

### Webhooks

Configurez des webhooks pour être notifié :
- Quand un planning est optimisé
- Quand un planning est appliqué
- En cas d'erreur d'optimisation

### API externe

Intégrez avec :
- **Systèmes de géolocalisation** (Google Maps, Mapbox)
- **Solutions de routage** (OSRM, GraphHopper)
- **Outils de reporting** (Tableau, Power BI)

## Exemples d'utilisation

### Cas d'usage 1 : Planning hebdomadaire

```javascript
// Optimiser le planning de la semaine prochaine
const result = await $api.post('/planning_optimizer.php?action=optimiser', {
  date_debut: '2024-01-15',
  date_fin: '2024-01-21',
  parametres_id: 1, // Optimisation équilibrée
  population_size: 50,
  generations: 100
});

if (result.success) {
  console.log(`Planning optimisé avec un score de ${result.data.score}`);
  console.log(`${result.data.statistiques.nombre_interventions} interventions planifiées`);
}
```

### Cas d'usage 2 : Optimisation urgente

```javascript
// Optimisation rapide pour interventions urgentes
const result = await $api.post('/planning_optimizer.php?action=optimiser', {
  date_debut: '2024-01-15',
  date_fin: '2024-01-15',
  parametres_id: 4, // Priorité urgence
  population_size: 20, // Population réduite pour rapidité
  generations: 50
});
```

### Cas d'usage 3 : Analyse des coûts

```javascript
// Optimisation axée sur la réduction des coûts
const result = await $api.post('/planning_optimizer.php?action=optimiser', {
  date_debut: '2024-01-15',
  date_fin: '2024-01-21',
  parametres_id: 3, // Priorité coût
  population_size: 100, // Population plus large
  generations: 200
});
```

## FAQ

### Q : L'optimisation est trop lente, que faire ?

**R :** Réduisez la taille de population et le nombre de générations, ou divisez la période en plus petites plages.

### Q : Le planning proposé n'est pas réaliste

**R :** Vérifiez :
- Les durées estimées des interventions
- Les disponibilités des techniciens
- Les zones d'intervention assignées
- Les coordonnées géographiques

### Q : Comment améliorer la qualité des plannings ?

**R :**
- Augmentez le nombre de générations
- Affinez les paramètres de poids
- Améliorez la précision des localisations
- Mettez à jour les temps de trajet

### Q : Peut-on optimiser plus de 100 interventions ?

**R :** Oui, mais le temps de calcul augmente exponentiellement. Considérez diviser en sous-problèmes ou utiliser des serveurs plus puissants.

## Support

Pour obtenir de l'aide :

1. Consultez les **logs de l'application**
2. Vérifiez la **documentation API**
3. Testez avec un **échantillon réduit** d'interventions
4. Contactez l'équipe de développement

---

*L'optimiseur de planning révolutionne la gestion des interventions en automatisant intelligemment la planification selon vos contraintes et objectifs.*