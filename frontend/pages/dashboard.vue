<template>
  <div>
    <!-- En-tête du dashboard -->
    <div class="mb-8">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Tableau de Bord Branchements
          </h1>
          <p class="text-gray-600 dark:text-gray-400">
            Vue d'ensemble des branchements électriques Enedis
          </p>
        </div>
        <div class="mt-4 lg:mt-0">
          <Button
            icon="pi pi-refresh"
            label="Actualiser"
            severity="secondary"
            @click="refreshData"
            :loading="loading"
          />
        </div>
      </div>
    </div>

    <!-- Notifications et alertes de délais -->
    <div v-if="alertesDelais.length > 0" class="mb-8">
      <Card class="border-orange-200 dark:border-orange-800">
        <template #header>
          <div
            class="flex items-center gap-2 p-4 border-b bg-orange-50 dark:bg-orange-900/20"
          >
            <i class="pi pi-exclamation-triangle text-orange-500"></i>
            <h3
              class="text-lg font-semibold text-orange-800 dark:text-orange-200"
            >
              Alertes de délais
            </h3>
            <Badge :value="alertesDelais.length" severity="warning" />
          </div>
        </template>
        <template #content>
          <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="alerte in alertesDelais"
                :key="alerte.id"
                class="flex items-center p-3 bg-white dark:bg-gray-800 rounded-lg border border-orange-200 dark:border-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors cursor-pointer"
                @click="$router.push(`/interventions/electrique/${alerte.id}`)"
              >
                <div class="flex-shrink-0 mr-3">
                  <i :class="[alerte.icon, alerte.colorClass]"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                  >
                    #{{ alerte.numero }}
                  </p>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    {{ alerte.message }}
                  </p>
                  <p
                    class="text-xs text-orange-600 dark:text-orange-400 font-medium"
                  >
                    {{ alerte.delai }}
                  </p>
                </div>
                <div class="flex-shrink-0">
                  <i class="pi pi-chevron-right text-gray-400"></i>
                </div>
              </div>
            </div>
            <div class="mt-4 text-center">
              <Button
                label="Voir tous les retards"
                icon="pi pi-clock"
                severity="warning"
                text
                size="small"
                @click="
                  $router.push('/interventions/electrique?filter=retards')
                "
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Métriques principales avec MetricsCard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <MetricsCard
        :metric="{
          title: 'Total Interventions',
          value: stats.total,
          icon: 'pi pi-clipboard',
          change: evolutionMensuelle,
          changePeriod: 'ce mois',
          current: stats.total,
          target: objectifMensuel,
          lastUpdate: new Date(),
          description: 'Branchements électriques',
        }"
        color="blue"
        :show-progress="true"
        :quick-actions="[
          { label: 'Voir tout', icon: 'pi pi-eye', type: 'view-all' },
          { label: 'Nouveau', icon: 'pi pi-plus', type: 'create' },
        ]"
        @action="handleMetricAction"
      />
      <MetricsCard
        :metric="{
          title: 'En cours',
          value: stats.enCours,
          icon: 'pi pi-clock',
          change:
            ((stats.enCours - statsHier.enCours) /
              Math.max(statsHier.enCours, 1)) *
            100,
          changePeriod: 'hier',
          description: 'Interventions actives',
          lastUpdate: new Date(),
        }"
        color="orange"
        :quick-actions="[
          { label: 'Voir', icon: 'pi pi-eye', type: 'view-in-progress' },
        ]"
        @action="handleMetricAction"
      />
      <MetricsCard
        :metric="{
          title: 'Terminées',
          value: stats.terminees,
          icon: 'pi pi-check-circle',
          change: tauxCompletion,
          changePeriod: 'ce mois',
          description: 'Interventions complétées',
          lastUpdate: new Date(),
        }"
        color="green"
        :quick-actions="[
          { label: 'Rapport', icon: 'pi pi-chart-bar', type: 'report' },
        ]"
        @action="handleMetricAction"
      />
      <MetricsCard
        :metric="{
          title: 'En attente',
          value: stats.enAttente,
          icon: 'pi pi-exclamation-triangle',
          change: stats.enAttente > 10 ? 15 : -5,
          changePeriod: 'semaine dernière',
          description: 'Nécessitent attention',
          lastUpdate: new Date(),
        }"
        color="red"
        :quick-actions="[
          { label: 'Prioriser', icon: 'pi pi-arrow-up', type: 'prioritize' },
        ]"
        @action="handleMetricAction"
      />
    </div>
    <!-- Métriques spécifiques Enedis -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Métriques par type réglementaire -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-flag text-blue-600 mr-2"></i>
              Types Réglementaires
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Type 1</span
              >
              <div class="flex items-center gap-2">
                <Badge :value="metriquesEnedis.types.type1" severity="info" />
                <span class="text-xs text-gray-500"
                  >{{
                    Math.round(
                      (metriquesEnedis.types.type1 / stats.total) * 100
                    )
                  }}%</span
                >
              </div>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Type 2</span
              >
              <div class="flex items-center gap-2">
                <Badge
                  :value="metriquesEnedis.types.type2"
                  severity="warning"
                />
                <span class="text-xs text-gray-500"
                  >{{
                    Math.round(
                      (metriquesEnedis.types.type2 / stats.total) * 100
                    )
                  }}%</span
                >
              </div>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Délai moy.</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.delais.delaiMoyenType1 }}j /
                {{ metriquesEnedis.delais.delaiMoyenType2 }}j</span
              >
            </div>
          </div>
        </template>
      </Card>
      <!-- Métriques par mode de pose -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-sitemap text-green-600 mr-2"></i>
              Modes de Pose
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Souterrain</span
              >
              <Badge
                :value="metriquesEnedis.modes.souterrain"
                severity="success"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Aérien</span
              >
              <Badge :value="metriquesEnedis.modes.aerien" severity="info" />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Aérosouterrain</span
              >
              <Badge
                :value="metriquesEnedis.modes.aerosouterrain"
                severity="warning"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Autres</span
              >
              <Badge
                :value="metriquesEnedis.modes.autres"
                severity="secondary"
              />
            </div>
          </div>
        </template>
      </Card>
      <!-- Distances et délais -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-map text-purple-600 mr-2"></i>
              Distances (LR/DI)
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >LR moyenne</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.distances.lrMoyenne }}m</span
              >
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >DI moyenne</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.distances.diMoyenne }}m</span
              >
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >DI > 30m</span
              >
              <div class="flex items-center gap-1">
                <Badge
                  :value="metriquesEnedis.distances.diSuperieures30"
                  severity="warning"
                />
                <i
                  class="pi pi-exclamation-triangle text-orange-500 text-xs"
                  v-tooltip="'Potentiels Type 2'"
                ></i>
              </div>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Distance max</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.distances.distanceMax }}m</span
              >
            </div>
          </div>
        </template>
      </Card>
      <!-- Alertes et délais -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-clock text-red-600 mr-2"></i>
              Délais & Alertes
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >En retard</span
              >
              <Badge
                :value="metriquesEnedis.delais.enRetard"
                severity="danger"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Alerte</span
              >
              <Badge
                :value="metriquesEnedis.delais.enAlerte"
                severity="warning"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Dans les temps</span
              >
              <Badge
                :value="metriquesEnedis.delais.dansLesTemps"
                severity="success"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Délai global moy.</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.delais.delaiGlobalMoyen }}j</span
              >
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Métriques des phases -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-cog text-orange-600 mr-2"></i>
              Phase Terrassement
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >En cours</span
              >
              <Badge
                :value="branchementStats.terrassement.enCours"
                severity="warning"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Terminés</span
              >
              <Badge
                :value="branchementStats.terrassement.termines"
                severity="success"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Durée moy.</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ branchementStats.terrassement.dureeMoyenne }}h</span
              >
            </div>
          </div>
        </template>
      </Card>
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-bolt text-blue-600 mr-2"></i>
              Phase Branchement
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >En cours</span
              >
              <Badge
                :value="branchementStats.branchement.enCours"
                severity="warning"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Terminés</span
              >
              <Badge
                :value="branchementStats.branchement.termines"
                severity="success"
              />
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Durée moy.</span
              >
              <span class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ branchementStats.branchement.dureeMoyenne }}h</span
              >
            </div>
          </div>
        </template>
      </Card>
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h4
              class="text-md font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-chart-line text-green-600 mr-2"></i>
              Performance
            </h4>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Taux de réussite</span
              >
              <span class="text-sm font-bold text-green-600"
                >{{ branchementStats.performance.tauxReussite }}%</span
              >
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Écart budget moy.</span
              >
              <span
                class="text-sm font-medium"
                :class="
                  branchementStats.performance.ecartBudget >= 0
                    ? 'text-red-600'
                    : 'text-green-600'
                "
              >
                {{ branchementStats.performance.ecartBudget > 0 ? "+" : ""
                }}{{ branchementStats.performance.ecartBudget }}%
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >CA du mois</span
              >
              <span
                class="text-sm font-bold text-gray-900 dark:text-gray-100"
                >{{ formatCurrency(branchementStats.performance.caMois) }}</span
              >
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Graphiques avancés des performances -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Graphique en anneau des types réglementaires -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Répartition Types
            </h3>
            <i class="pi pi-chart-pie text-blue-600"></i>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-4">
            <div class="relative h-48 flex items-center justify-center">
              <div class="text-center">
                <div
                  class="text-3xl font-bold text-gray-900 dark:text-gray-100"
                >
                  {{ stats.total }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  Branchements
                </div>
              </div>
              <!-- Cercle Type 1 -->
              <svg class="absolute inset-0 w-full h-full -rotate-90">
                <circle
                  cx="50%"
                  cy="50%"
                  r="70"
                  fill="none"
                  stroke="#e5e7eb"
                  stroke-width="12"
                />
                <circle
                  cx="50%"
                  cy="50%"
                  r="70"
                  fill="none"
                  stroke="#3b82f6"
                  stroke-width="12"
                  :stroke-dasharray="`${
                    (metriquesEnedis.types.type1 / stats.total) * 439.6 || 0
                  } 439.6`"
                  stroke-linecap="round"
                />
              </svg>
            </div>
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                  <span class="text-sm">Type 1 (DI ≤ 30m)</span>
                </div>
                <span class="font-medium">{{
                  metriquesEnedis.types.type1
                }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                  <span class="text-sm">Type 2 (DI > 30m)</span>
                </div>
                <span class="font-medium">{{
                  metriquesEnedis.types.type2
                }}</span>
              </div>
            </div>
          </div>
        </template>
      </Card>
      <!-- Graphique des performances mensuelles -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Tendances
            </h3>
            <i class="pi pi-chart-line text-green-600"></i>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-4">
            <div class="h-48 flex items-end justify-between space-x-2">
              <div
                v-for="(mois, index) in tendancesMensuelles"
                :key="index"
                class="flex-1 flex flex-col items-center"
              >
                <div
                  class="w-full bg-gradient-to-t from-blue-500 to-blue-300 rounded-t-sm transition-all duration-500"
                  :style="{ height: `${(mois.interventions / 20) * 100}%` }"
                  v-tooltip="`${mois.nom}: ${mois.interventions} branchements`"
                ></div>
                <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">{{
                  mois.nom
                }}</span>
              </div>
            </div>
            <div class="text-center">
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Évolution sur 6 mois
              </div>
              <div class="flex items-center justify-center gap-2 mt-1">
                <i
                  class="pi pi-arrow-up text-green-500"
                  v-if="evolutionMensuelle > 0"
                ></i>
                <i
                  class="pi pi-arrow-down text-red-500"
                  v-else-if="evolutionMensuelle < 0"
                ></i>
                <i class="pi pi-minus text-gray-400" v-else></i>
                <span
                  class="text-sm font-medium"
                  :class="
                    evolutionMensuelle > 0
                      ? 'text-green-600'
                      : evolutionMensuelle < 0
                      ? 'text-red-600'
                      : 'text-gray-600'
                  "
                >
                  {{ Math.abs(evolutionMensuelle) }}%
                </span>
              </div>
            </div>
          </div>
        </template>
      </Card>
      <!-- Indicateurs de performance clés -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              KPI
            </h3>
            <i class="pi pi-star text-yellow-600"></i>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-4">
            <!-- Score de performance global -->
            <div class="text-center">
              <div class="relative w-24 h-24 mx-auto">
                <svg class="w-24 h-24 transform -rotate-90">
                  <circle
                    cx="48"
                    cy="48"
                    r="40"
                    stroke="#e5e7eb"
                    stroke-width="8"
                    fill="none"
                  />
                  <circle
                    cx="48"
                    cy="48"
                    r="40"
                    stroke="#10b981"
                    stroke-width="8"
                    fill="none"
                    :stroke-dasharray="`${
                      (scorePerformance / 100) * 251.2 || 0
                    } 251.2`"
                    stroke-linecap="round"
                  />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                  <span
                    class="text-xl font-bold text-gray-900 dark:text-gray-100"
                    >{{ scorePerformance }}%</span
                  >
                </div>
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                Score Global
              </div>
            </div>
            <!-- Détails KPI -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600 dark:text-gray-400"
                  >Respect délais</span
                >
                <span class="text-xs font-medium text-green-600"
                  >{{
                    Math.round(
                      (metriquesEnedis.delais.dansLesTemps / stats.total) * 100
                    ) || 0
                  }}%</span
                >
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600 dark:text-gray-400"
                  >Efficacité type</span
                >
                <span class="text-xs font-medium text-blue-600"
                  >{{ efficaciteType }}%</span
                >
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-600 dark:text-gray-400"
                  >Satisfaction</span
                >
                <span class="text-xs font-medium text-purple-600"
                  >{{ satisfactionClient }}%</span
                >
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Graphiques des délais Enedis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Graphique délais par type -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Délais par type réglementaire
            </h3>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-4">
            <div
              class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 bg-blue-500 rounded"></div>
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                  >Type 1 (DI ≤ 30m)</span
                >
              </div>
              <span class="text-sm font-bold text-blue-600"
                >{{ metriquesEnedis.delais.delaiMoyenType1 }}j</span
              >
            </div>
            <div
              class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 bg-orange-500 rounded"></div>
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100"
                  >Type 2 (DI > 30m)</span
                >
              </div>
              <span class="text-sm font-bold text-orange-600"
                >{{ metriquesEnedis.delais.delaiMoyenType2 }}j</span
              >
            </div>
            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400"
                  >Délai global moyen</span
                >
                <span class="text-sm font-bold text-gray-900 dark:text-gray-100"
                  >{{ metriquesEnedis.delais.delaiGlobalMoyen }}j</span
                >
              </div>
              <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Objectif Enedis : ≤ 21 jours
              </div>
            </div>
            <!-- Alertes si dépassement -->
            <div
              v-if="metriquesEnedis.delais.delaiGlobalMoyen > 21"
              class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800"
            >
              <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-red-500"></i>
                <span class="text-sm font-medium text-red-700 dark:text-red-300"
                  >Objectif Enedis dépassé</span
                >
              </div>
              <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                +{{ metriquesEnedis.delais.delaiGlobalMoyen - 21 }} jours
                au-dessus de l'objectif
              </div>
            </div>
          </div>
        </template>
      </Card>
      <!-- Répartition des états de délais -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              État des délais
            </h3>
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-4">
            <!-- Dans les temps -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-check-circle text-green-500"></i>
                <span class="text-sm text-gray-600 dark:text-gray-400"
                  >Dans les temps</span
                >
              </div>
              <span
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.delais.dansLesTemps }}</span
              >
            </div>
            <ProgressBar
              :value="
                stats.total > 0
                  ? (metriquesEnedis.delais.dansLesTemps / stats.total) * 100
                  : 0
              "
              :showValue="false"
              style="height: 8px"
              class="mb-2 !bg-green-500"
            />
            <!-- En alerte -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-orange-500"></i>
                <span class="text-sm text-gray-600 dark:text-gray-400"
                  >En alerte</span
                >
              </div>
              <span
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.delais.enAlerte }}</span
              >
            </div>
            <ProgressBar
              :value="
                stats.total > 0
                  ? (metriquesEnedis.delais.enAlerte / stats.total) * 100
                  : 0
              "
              :showValue="false"
              style="height: 8px"
              class="mb-2 !bg-orange-500"
            />
            <!-- En retard -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-times-circle text-red-500"></i>
                <span class="text-sm text-gray-600 dark:text-gray-400"
                  >En retard</span
                >
              </div>
              <span
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ metriquesEnedis.delais.enRetard }}</span
              >
            </div>
            <ProgressBar
              :value="
                stats.total > 0
                  ? (metriquesEnedis.delais.enRetard / stats.total) * 100
                  : 0
              "
              :showValue="false"
              style="height: 8px"
              class="mb-2 !bg-red-500"
            />
            <div class="mt-4 text-center">
              <div class="text-xs text-gray-500 dark:text-gray-400">
                Taux de respect des délais :
                <span
                  class="font-bold"
                  :class="
                    (metriquesEnedis.delais.dansLesTemps / stats.total) * 100 >=
                    85
                      ? 'text-green-600'
                      : 'text-red-600'
                  "
                >
                  {{
                    stats.total > 0
                      ? Math.round(
                          (metriquesEnedis.delais.dansLesTemps / stats.total) *
                            100
                        )
                      : 0
                  }}%
                </span>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Graphiques et activité récente -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Graphique des statuts -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Répartition des statuts
            </h3>
            <Button
              icon="pi pi-refresh"
              text
              rounded
              size="small"
              @click="refreshData"
              v-tooltip.top="'Actualiser'"
            />
          </div>
        </template>
        <template #content>
          <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner
              style="width: 50px; height: 50px"
              strokeWidth="4"
            />
          </div>
          <div v-else class="p-4 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >En cours</span
              >
              <span
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ stats.enCours }}</span
              >
            </div>
            <ProgressBar
              :value="stats.total > 0 ? (stats.enCours / stats.total) * 100 : 0"
              :showValue="false"
              style="height: 8px"
              class="mb-2"
            />
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >Terminées</span
              >
              <span
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ stats.terminees }}</span
              >
            </div>
            <ProgressBar
              :value="
                stats.total > 0 ? (stats.terminees / stats.total) * 100 : 0
              "
              :showValue="false"
              style="height: 8px"
              class="mb-2 !bg-green-500"
            />
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600 dark:text-gray-400"
                >En attente</span
              >
              <span
                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                >{{ stats.enAttente }}</span
              >
            </div>
            <ProgressBar
              :value="
                stats.total > 0 ? (stats.enAttente / stats.total) * 100 : 0
              "
              :showValue="false"
              style="height: 8px"
            />
          </div>
        </template>
      </Card>
      <!-- Activité récente -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Activité récente
            </h3>
            <Button
              label="Voir tout"
              icon="pi pi-arrow-right"
              text
              size="small"
              @click="$router.push('/interventions')"
            />
          </div>
        </template>
        <template #content>
          <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner
              style="width: 50px; height: 50px"
              strokeWidth="4"
            />
          </div>
          <div
            v-else-if="recentInterventions.length === 0"
            class="p-4 text-center text-gray-500 dark:text-gray-400"
          >
            <i class="pi pi-info-circle text-2xl mb-2"></i>
            <p>Aucune intervention récente</p>
          </div>
          <div v-else class="p-4 space-y-4 max-h-80 overflow-y-auto">
            <div
              v-for="intervention in recentInterventions"
              :key="intervention.id"
              class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer"
              @click="$router.push(`/interventions/${intervention.id}`)"
            >
              <div class="flex-shrink-0 mr-3">
                <Badge
                  :value="intervention.statut"
                  :severity="getStatusSeverity(intervention.statut)"
                  size="small"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                >
                  {{ intervention.titre }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ intervention.client_nom }} •
                  {{ formatDate(intervention.date_creation) }}
                </p>
              </div>
              <div class="flex-shrink-0">
                <i class="pi pi-chevron-right text-gray-400"></i>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Section prédictions et recommandations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Prédictions IA -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3
              class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-brain text-purple-600 mr-2"></i>
              Prédictions IA
            </h3>
            <Badge value="NOUVEAU" severity="info" />
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-4">
            <!-- Prédiction de charge -->
            <div
              class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800"
            >
              <div class="flex items-center gap-3">
                <i class="pi pi-calendar text-blue-600"></i>
                <div class="flex-1">
                  <h4
                    class="text-sm font-medium text-blue-900 dark:text-blue-100"
                  >
                    Prédiction charge
                  </h4>
                  <p class="text-xs text-blue-700 dark:text-blue-300">
                    +{{ predictionCharge.augmentation }}% de branchements prévus
                    le mois prochain
                  </p>
                </div>
                <div class="text-right">
                  <div class="text-lg font-bold text-blue-600">
                    {{ predictionCharge.nombre }}
                  </div>
                  <div class="text-xs text-blue-500">branchements</div>
                </div>
              </div>
            </div>
            <!-- Recommandation ressources -->
            <div
              class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800"
            >
              <div class="flex items-center gap-3">
                <i class="pi pi-users text-orange-600"></i>
                <div class="flex-1">
                  <h4
                    class="text-sm font-medium text-orange-900 dark:text-orange-100"
                  >
                    Ressources recommandées
                  </h4>
                  <p class="text-xs text-orange-700 dark:text-orange-300">
                    {{ recommendationRessources.techniciens }} techniciens
                    supplémentaires nécessaires
                  </p>
                </div>
                <Button
                  icon="pi pi-plus"
                  size="small"
                  severity="warning"
                  @click="planifierRessources"
                />
              </div>
            </div>
            <!-- Optimisation délais -->
            <div
              class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800"
            >
              <div class="flex items-center gap-3">
                <i class="pi pi-clock text-green-600"></i>
                <div class="flex-1">
                  <h4
                    class="text-sm font-medium text-green-900 dark:text-green-100"
                  >
                    Optimisation possible
                  </h4>
                  <p class="text-xs text-green-700 dark:text-green-300">
                    -{{ optimisationDelais.reduction }}j de délai moyen avec une
                    meilleure planification
                  </p>
                </div>
                <Badge
                  :value="`-${optimisationDelais.reduction}j`"
                  severity="success"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
      <!-- Alertes et actions intelligentes -->
      <Card>
        <template #header>
          <div class="flex items-center justify-between p-4 border-b">
            <h3
              class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center"
            >
              <i class="pi pi-bell text-red-600 mr-2"></i>
              Actions Prioritaires
            </h3>
            <Badge :value="actionsIntelligentes.length" severity="danger" />
          </div>
        </template>
        <template #content>
          <div class="p-4 space-y-3">
            <div
              v-for="action in actionsIntelligentes"
              :key="action.id"
              class="flex items-center p-3 rounded-lg border transition-colors cursor-pointer"
              :class="
                action.urgence === 'haute'
                  ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30'
                  : action.urgence === 'moyenne'
                  ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800 hover:bg-orange-100 dark:hover:bg-orange-900/30'
                  : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30'
              "
              @click="executerAction(action)"
            >
              <div class="flex-shrink-0 mr-3">
                <i
                  :class="[
                    action.icon,
                    action.urgence === 'haute'
                      ? 'text-red-500'
                      : action.urgence === 'moyenne'
                      ? 'text-orange-500'
                      : 'text-blue-500',
                  ]"
                ></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ action.titre }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                  {{ action.description }}
                </p>
              </div>
              <div class="flex-shrink-0 flex items-center gap-2">
                <Badge
                  :value="action.impact"
                  :severity="
                    action.urgence === 'haute'
                      ? 'danger'
                      : action.urgence === 'moyenne'
                      ? 'warning'
                      : 'info'
                  "
                  size="small"
                />
                <i class="pi pi-chevron-right text-gray-400"></i>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
    <!-- Actions rapides -->
    <Card>
      <template #header>
        <div class="p-4 border-b">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Actions rapides
          </h3>
        </div>
      </template>
      <template #content>
        <div class="p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Button
              label="Nouvelle intervention"
              icon="pi pi-plus"
              class="p-button-primary justify-start"
              @click="$router.push('/interventions/create')"
            />
            <Button
              label="Mes branchements"
              icon="pi pi-bolt"
              class="p-button-outlined justify-start"
              @click="$router.push('/interventions/electrique')"
            />
            <Button
              label="Planning"
              icon="pi pi-calendar"
              class="p-button-outlined justify-start"
              @click="$router.push('/planning')"
            />
            <Button
              label="Gestion clients"
              icon="pi pi-users"
              class="p-button-outlined justify-start"
              @click="$router.push('/clients')"
            />
            <Button
              v-if="user?.role === 'admin' || user?.role === 'manager'"
              label="Gestion utilisateurs"
              icon="pi pi-user-edit"
              class="p-button-outlined justify-start"
              @click="$router.push('/users')"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>
<script setup>
definePageMeta({
  middleware: ["auth"],
});
const { user } = useAuth();
const { interventions, fetchInterventions, loading } = useInterventions();
const stats = ref({
  total: 0,
  enCours: 0,
  terminees: 0,
  enAttente: 0,
});
const recentInterventions = ref([]);
const branchementStats = ref({
  terrassement: {
    enCours: 0,
    termines: 0,
    dureeMoyenne: 0,
  },
  branchement: {
    enCours: 0,
    termines: 0,
    dureeMoyenne: 0,
  },
  performance: {
    tauxReussite: 0,
    ecartBudget: 0,
    caMois: 0,
  },
});
// Nouvelles métriques spécifiques Enedis
const metriquesEnedis = ref({
  types: {
    type1: 0,
    type2: 0,
  },
  modes: {
    souterrain: 0,
    aerien: 0,
    aerosouterrain: 0,
    autres: 0,
  },
  distances: {
    lrMoyenne: 0,
    diMoyenne: 0,
    diSuperieures30: 0,
    distanceMax: 0,
  },
  delais: {
    enRetard: 0,
    enAlerte: 0,
    dansLesTemps: 0,
    delaiGlobalMoyen: 0,
    delaiMoyenType1: 0,
    delaiMoyenType2: 0,
  },
});
// Alertes de délais
const alertesDelais = ref([]);
// Nouvelles métriques avancées pour Sprint 3
const tendancesMensuelles = ref([
  { nom: "Avr", interventions: 12 },
  { nom: "Mai", interventions: 18 },
  { nom: "Jun", interventions: 15 },
  { nom: "Jul", interventions: 22 },
  { nom: "Aoû", interventions: 19 },
  { nom: "Sep", interventions: 25 },
]);
const evolutionMensuelle = ref(15.2); // +15.2% par rapport au mois précédent
const scorePerformance = ref(87); // Score global de performance
const efficaciteType = ref(92); // Efficacité dans la classification des types
const satisfactionClient = ref(96); // Satisfaction client simulée
// Prédictions et recommandations IA
const predictionCharge = ref({
  augmentation: 18,
  nombre: 32,
  confidence: 0.87,
});
const recommendationRessources = ref({
  techniciens: 2,
  specialite: "souterrain",
});
const optimisationDelais = ref({
  reduction: 3.5,
  methode: "Réorganisation planning",
});
// Données supplémentaires pour MetricsCard
const objectifMensuel = ref(150);
const tauxCompletion = ref(85);
const statsHier = ref({
  enCours: stats.value.enCours - 2,
  terminees: stats.value.terminees - 3,
  enAttente: stats.value.enAttente + 1,
});
// Actions intelligentes prioritaires
const actionsIntelligentes = ref([
  {
    id: 1,
    titre: "Retards critiques",
    description: "3 branchements dépassent 30 jours",
    icon: "pi pi-exclamation-triangle",
    urgence: "haute",
    impact: "3 clients",
    action: "gerer_retards",
  },
  {
    id: 2,
    titre: "Incohérences Type/DI",
    description: "2 Type 1 avec DI > 30m détectés",
    icon: "pi pi-flag",
    urgence: "moyenne",
    impact: "2 dossiers",
    action: "corriger_types",
  },
  {
    id: 3,
    titre: "Optimisation planning",
    description: "Regrouper 4 interventions secteur Nord",
    icon: "pi pi-map",
    urgence: "basse",
    impact: "-2h",
    action: "optimiser_planning",
  },
]);
const getStatusSeverity = (status) => {
  switch (status) {
    case "En cours":
      return "warning";
    case "Terminée":
      return "success";
    case "En attente":
      return "info";
    case "Annulée":
      return "danger";
    default:
      return "info";
  }
};
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
};
const formatCurrency = (amount) => {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(amount);
};
const calculateStats = () => {
  const interventionsList = [...(interventions.value || [])];
  stats.value = {
    total: interventionsList.length,
    enCours: interventionsList.filter((i) => i.statut === "En cours").length,
    terminees: interventionsList.filter((i) => i.statut === "Terminée").length,
    enAttente: interventionsList.filter((i) => i.statut === "En attente")
      .length,
  };
  // Calcul des statistiques spécifiques aux branchements
  calculateBranchementStats(interventionsList);
  // Calcul des nouvelles métriques Enedis
  calculateMetriquesEnedis(interventionsList);
  // Calcul des alertes de délais
  calculateAlertesDelais(interventionsList);
  // Les 5 interventions les plus récentes
  recentInterventions.value = interventionsList
    .sort((a, b) => new Date(b.date_creation) - new Date(a.date_creation))
    .slice(0, 5);
};
const calculateBranchementStats = (interventionsList) => {
  // Simulation de données pour les phases de branchement
  // Dans un vrai contexte, ces données viendraient de l'API avec les détails des phases
  const terrassementInterventions = interventionsList.filter(
    (i) => i.phase_terrassement
  );
  const branchementInterventions = interventionsList.filter(
    (i) => i.phase_branchement
  );
  // Calculs pour la phase terrassement
  branchementStats.value.terrassement = {
    enCours: Math.floor(interventionsList.length * 0.3), // 30% en cours
    termines: Math.floor(interventionsList.length * 0.6), // 60% terminés
    dureeMoyenne: 4.5, // moyenne de 4.5h pour le terrassement
  };
  // Calculs pour la phase branchement
  branchementStats.value.branchement = {
    enCours: Math.floor(interventionsList.length * 0.25), // 25% en cours
    termines: Math.floor(interventionsList.length * 0.55), // 55% terminés
    dureeMoyenne: 3.2, // moyenne de 3.2h pour le branchement
  };
  // Calculs de performance
  const totalTerminees = stats.value.terminees;
  const totalInterventions = stats.value.total;
  branchementStats.value.performance = {
    tauxReussite:
      totalInterventions > 0
        ? Math.round((totalTerminees / totalInterventions) * 100)
        : 0,
    ecartBudget: Math.round((Math.random() - 0.5) * 20), // Simulation d'écart de -10% à +10%
    caMois: 125000 + Math.random() * 50000, // CA simulé entre 125k et 175k
  };
};
const calculateMetriquesEnedis = (interventionsList) => {
  // Métriques par type réglementaire
  const type1Count = interventionsList.filter(
    (i) => i.type_reglementaire === "type_1"
  ).length;
  const type2Count = interventionsList.filter(
    (i) => i.type_reglementaire === "type_2"
  ).length;
  metriquesEnedis.value.types = {
    type1: type1Count,
    type2: type2Count,
  };
  // Métriques par mode de pose
  const souterrainCount = interventionsList.filter(
    (i) => i.mode_pose === "souterrain"
  ).length;
  const aerienCount = interventionsList.filter(
    (i) => i.mode_pose === "aerien"
  ).length;
  const aerosouterrainCount = interventionsList.filter(
    (i) => i.mode_pose === "aerosouterrain"
  ).length;
  const autresCount =
    interventionsList.length -
    souterrainCount -
    aerienCount -
    aerosouterrainCount;
  metriquesEnedis.value.modes = {
    souterrain: souterrainCount,
    aerien: aerienCount,
    aerosouterrain: aerosouterrainCount,
    autres: autresCount,
  };
  // Métriques de distances
  const interventionsAvecDistances = interventionsList.filter(
    (i) =>
      i.longueur_liaison_reseau !== null &&
      i.longueur_derivation_individuelle !== null
  );
  if (interventionsAvecDistances.length > 0) {
    const lrMoyenne =
      interventionsAvecDistances.reduce(
        (sum, i) => sum + (i.longueur_liaison_reseau || 0),
        0
      ) / interventionsAvecDistances.length;
    const diMoyenne =
      interventionsAvecDistances.reduce(
        (sum, i) => sum + (i.longueur_derivation_individuelle || 0),
        0
      ) / interventionsAvecDistances.length;
    const diSuperieures30 = interventionsList.filter(
      (i) => (i.longueur_derivation_individuelle || 0) > 30
    ).length;
    const distanceMax = Math.max(
      ...interventionsList.map((i) => i.distance_raccordement || 0)
    );
    metriquesEnedis.value.distances = {
      lrMoyenne: Math.round(lrMoyenne * 10) / 10,
      diMoyenne: Math.round(diMoyenne * 10) / 10,
      diSuperieures30,
      distanceMax,
    };
  }
  // Métriques de délais (simulation basée sur les données existantes)
  const totalInterventions = interventionsList.length;
  const enRetard = Math.floor(totalInterventions * 0.15); // 15% en retard
  const enAlerte = Math.floor(totalInterventions * 0.25); // 25% en alerte
  const dansLesTemps = totalInterventions - enRetard - enAlerte;
  metriquesEnedis.value.delais = {
    enRetard,
    enAlerte,
    dansLesTemps,
    delaiGlobalMoyen: 18, // 18 jours en moyenne
    delaiMoyenType1: 15, // Type 1 plus rapide
    delaiMoyenType2: 22, // Type 2 plus long
  };
};
const calculateAlertesDelais = (interventionsList) => {
  const alertes = [];
  const today = new Date();
  interventionsList.forEach((intervention) => {
    if (intervention.statut === "Terminée") return;
    const dateCreation = new Date(intervention.date_creation);
    const joursEcoules = Math.floor(
      (today - dateCreation) / (1000 * 60 * 60 * 24)
    );
    // Seuils d'alertes selon le type
    const seuils = {
      type_1: { alerte: 18, retard: 21 },
      type_2: { alerte: 25, retard: 30 },
      default: { alerte: 20, retard: 25 },
    };
    const seuil = seuils[intervention.type_reglementaire] || seuils.default;
    if (joursEcoules >= seuil.retard) {
      // En retard critique
      alertes.push({
        id: intervention.id,
        numero: intervention.numero,
        message: `Retard critique - ${intervention.client_nom}`,
        delai: `+${joursEcoules - seuil.retard} jours de retard`,
        icon: "pi pi-times-circle",
        colorClass: "text-red-500",
        type: "retard",
        priorite: 3,
      });
    } else if (joursEcoules >= seuil.alerte) {
      // En alerte
      alertes.push({
        id: intervention.id,
        numero: intervention.numero,
        message: `Délai limite approche - ${intervention.client_nom}`,
        delai: `${seuil.retard - joursEcoules} jours restants`,
        icon: "pi pi-exclamation-triangle",
        colorClass: "text-orange-500",
        type: "alerte",
        priorite: 2,
      });
    }
    // Vérification des incohérences Type 1/Type 2
    if (
      intervention.type_reglementaire === "type_1" &&
      (intervention.longueur_derivation_individuelle || 0) > 30
    ) {
      alertes.push({
        id: intervention.id,
        numero: intervention.numero,
        message: `Incohérence DI > 30m pour Type 1 - ${intervention.client_nom}`,
        delai: `DI: ${intervention.longueur_derivation_individuelle}m`,
        icon: "pi pi-flag",
        colorClass: "text-purple-500",
        type: "incoherence",
        priorite: 1,
      });
    }
  });
  // Trier par priorité (retards critiques en premier)
  alertesDelais.value = alertes
    .sort((a, b) => b.priorite - a.priorite)
    .slice(0, 9); // Maximum 9 alertes affichées
};
const refreshData = async () => {
  await fetchInterventions();
  calculateStats();
  calculateTendances();
  calculatePredictions();
};
/**
 * Calcule les tendances mensuelles dynamiquement
 */
const calculateTendances = () => {
  const interventionsList = [...(interventions.value || [])];
  const dernierMois =
    interventionsList.length > 0 ? interventionsList.length : 25;
  const moisPrecedent = Math.max(dernierMois - 3, 15);
  evolutionMensuelle.value = Math.round(
    ((dernierMois - moisPrecedent) / moisPrecedent) * 100
  );
  // Mise à jour du dernier mois dans les tendances
  if (tendancesMensuelles.value.length > 0) {
    tendancesMensuelles.value[
      tendancesMensuelles.value.length - 1
    ].interventions = dernierMois;
  }
};
/**
 * Calcule les prédictions basées sur les données actuelles
 */
const calculatePredictions = () => {
  const interventionsList = [...(interventions.value || [])];
  // Prédiction de charge basée sur les tendances
  const croissance =
    evolutionMensuelle.value > 0 ? evolutionMensuelle.value : 8;
  predictionCharge.value = {
    augmentation: Math.min(croissance + 3, 25),
    nombre: Math.floor(interventionsList.length * (1 + croissance / 100)) + 7,
    confidence: 0.87,
  };
  // Recommandations ressources basées sur la charge prévue
  const surcharge =
    predictionCharge.value.nombre > interventionsList.length * 1.2;
  recommendationRessources.value = {
    techniciens: surcharge ? 3 : 2,
    specialite: "souterrain",
  };
  // Optimisation délais basée sur les retards actuels
  const retardsPourcentage =
    (metriquesEnedis.value.delais.enRetard /
      Math.max(interventionsList.length, 1)) *
    100;
  optimisationDelais.value = {
    reduction: retardsPourcentage > 15 ? 4.2 : 2.1,
    methode: "Réorganisation planning",
  };
  // Mise à jour des actions intelligentes
  updateActionsIntelligentes(interventionsList);
};
/**
 * Met à jour les actions intelligentes basées sur les données réelles
 */
const updateActionsIntelligentes = (interventionsList) => {
  const actions = [];
  // Retards critiques
  const retardsCritiques = metriquesEnedis.value.delais.enRetard;
  if (retardsCritiques > 0) {
    actions.push({
      id: 1,
      titre: "Retards critiques",
      description: `${retardsCritiques} branchements en retard`,
      icon: "pi pi-exclamation-triangle",
      urgence: "haute",
      impact: `${retardsCritiques} clients`,
      action: "gerer_retards",
    });
  }
  // Incohérences Type/DI
  const incoherences = interventionsList.filter(
    (i) =>
      i.type_reglementaire === "type_1" &&
      (i.longueur_derivation_individuelle || 0) > 30
  ).length;
  if (incoherences > 0) {
    actions.push({
      id: 2,
      titre: "Incohérences Type/DI",
      description: `${incoherences} Type 1 avec DI > 30m détectés`,
      icon: "pi pi-flag",
      urgence: "moyenne",
      impact: `${incoherences} dossiers`,
      action: "corriger_types",
    });
  }
  // Optimisation possible
  if (interventionsList.length > 5) {
    actions.push({
      id: 3,
      titre: "Optimisation planning",
      description: "Possibilité de regroupement par secteur",
      icon: "pi pi-map",
      urgence: "basse",
      impact: "-2h",
      action: "optimiser_planning",
    });
  }
  actionsIntelligentes.value = actions;
};
/**
 * Planifier des ressources supplémentaires
 */
const planifierRessources = () => {
  // Redirection vers la page de gestion des utilisateurs ou planning
  navigateTo("/users?action=ajouter_technicien");
};
/**
 * Exécuter une action intelligente
 */
const executerAction = (action) => {
  switch (action.action) {
    case "gerer_retards":
      navigateTo("/interventions/electrique?filter=retards");
      break;
    case "corriger_types":
      navigateTo("/interventions/electrique?filter=incoherences");
      break;
    case "optimiser_planning":
      navigateTo("/planning?mode=optimisation");
      break;
    default:
      console.log("Action non implémentée:", action.action);
  }
};
// Gestion des actions des MetricsCard
const handleMetricAction = (actionType, metric) => {
  switch (actionType) {
    case "view-all":
      navigateTo("/interventions/electrique");
      break;
    case "create":
      navigateTo("/interventions/electrique/create");
      break;
    case "view-in-progress":
      navigateTo("/interventions/electrique?status=en_cours");
      break;
    case "report":
      navigateTo("/system/metrics");
      break;
    case "prioritize":
      navigateTo("/interventions/electrique?status=en_attente&sort=priority");
      break;
    default:
      console.log("Action métrique non définie:", actionType);
  }
};
onMounted(async () => {
  await fetchInterventions();
  calculateStats();
  calculateTendances();
  calculatePredictions();
});
watch(
  interventions,
  () => {
    calculateStats();
    calculateTendances();
    calculatePredictions();
  },
  { deep: true }
);
</script>
