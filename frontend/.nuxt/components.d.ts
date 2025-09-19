
import type { DefineComponent, SlotsType } from 'vue'
type IslandComponent<T extends DefineComponent> = T & DefineComponent<{}, {refresh: () => Promise<void>}, {}, {}, {}, {}, {}, {}, {}, {}, {}, {}, SlotsType<{ fallback: { error: unknown } }>>
type HydrationStrategies = {
  hydrateOnVisible?: IntersectionObserverInit | true
  hydrateOnIdle?: number | true
  hydrateOnInteraction?: keyof HTMLElementEventMap | Array<keyof HTMLElementEventMap> | true
  hydrateOnMediaQuery?: string
  hydrateAfter?: number
  hydrateWhen?: boolean
  hydrateNever?: true
}
type LazyComponent<T> = (T & DefineComponent<HydrationStrategies, {}, {}, {}, {}, {}, {}, { hydrated: () => void }>)
interface _GlobalComponents {
      'AppHeader': typeof import("../components/AppHeader.vue")['default']
    'AppSidebar': typeof import("../components/AppSidebar.vue")['default']
    'LoginForm': typeof import("../components/Auth/LoginForm.vue")['default']
    'ClientForm': typeof import("../components/Clients/ClientForm.vue")['default']
    'BudgetComparison': typeof import("../components/Interventions/BudgetComparison.vue")['default']
    'InterventionCard': typeof import("../components/Interventions/InterventionCard.vue")['default']
    'InterventionElectrique': typeof import("../components/Interventions/InterventionElectrique.vue")['default']
    'InterventionFilters': typeof import("../components/Interventions/InterventionFilters.vue")['default']
    'InterventionForm': typeof import("../components/Interventions/InterventionForm.vue")['default']
    'InterventionSummary': typeof import("../components/Interventions/InterventionSummary.vue")['default']
    'PhaseCard': typeof import("../components/Interventions/PhaseCard.vue")['default']
    'PhaseDetailSummary': typeof import("../components/Interventions/PhaseDetailSummary.vue")['default']
    'ProcessTimeline': typeof import("../components/Interventions/ProcessTimeline.vue")['default']
    'SessionHistory': typeof import("../components/Interventions/SessionHistory.vue")['default']
    'TailwindTest': typeof import("../components/Test/TailwindTest.vue")['default']
    'DelayIndicator': typeof import("../components/UI/DelayIndicator.vue")['default']
    'InterventionCardCompact': typeof import("../components/UI/InterventionCardCompact.vue")['default']
    'MetricsCard': typeof import("../components/UI/MetricsCard.vue")['default']
    'MultiSelect': typeof import("../components/UI/MultiSelect.vue")['default']
    'NotificationCenter': typeof import("../components/UI/NotificationCenter.vue")['default']
    'TimelineComponent': typeof import("../components/UI/TimelineComponent.vue")['default']
    'TypeSelector': typeof import("../components/UI/TypeSelector.vue")['default']
    'FileUpload': typeof import("../components/Upload/FileUpload.vue")['default']
    'UserForm': typeof import("../components/Users/UserForm.vue")['default']
    'NuxtWelcome': typeof import("../node_modules/nuxt/dist/app/components/welcome.vue")['default']
    'NuxtLayout': typeof import("../node_modules/nuxt/dist/app/components/nuxt-layout")['default']
    'NuxtErrorBoundary': typeof import("../node_modules/nuxt/dist/app/components/nuxt-error-boundary.vue")['default']
    'ClientOnly': typeof import("../node_modules/nuxt/dist/app/components/client-only")['default']
    'DevOnly': typeof import("../node_modules/nuxt/dist/app/components/dev-only")['default']
    'ServerPlaceholder': typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']
    'NuxtLink': typeof import("../node_modules/nuxt/dist/app/components/nuxt-link")['default']
    'NuxtLoadingIndicator': typeof import("../node_modules/nuxt/dist/app/components/nuxt-loading-indicator")['default']
    'NuxtTime': typeof import("../node_modules/nuxt/dist/app/components/nuxt-time.vue")['default']
    'NuxtRouteAnnouncer': typeof import("../node_modules/nuxt/dist/app/components/nuxt-route-announcer")['default']
    'NuxtImg': typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtImg']
    'NuxtPicture': typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtPicture']
    'DatePicker': typeof import("primevue/datepicker")['default']
    'Dropdown': typeof import("primevue/dropdown")['default']
    'InputNumber': typeof import("primevue/inputnumber")['default']
    'InputText': typeof import("primevue/inputtext")['default']
    'Password': typeof import("primevue/password")['default']
    'Select': typeof import("primevue/select")['default']
    'Slider': typeof import("primevue/slider")['default']
    'Textarea': typeof import("primevue/textarea")['default']
    'Button': typeof import("primevue/button")['default']
    'Column': typeof import("primevue/column")['default']
    'DataTable': typeof import("primevue/datatable")['default']
    'Card': typeof import("primevue/card")['default']
    'ConfirmDialog': typeof import("primevue/confirmdialog")['default']
    'Dialog': typeof import("primevue/dialog")['default']
    'Breadcrumb': typeof import("primevue/breadcrumb")['default']
    'MegaMenu': typeof import("primevue/megamenu")['default']
    'PanelMenu': typeof import("primevue/panelmenu")['default']
    'Message': typeof import("primevue/message")['default']
    'Toast': typeof import("primevue/toast")['default']
    'Avatar': typeof import("primevue/avatar")['default']
    'Badge': typeof import("primevue/badge")['default']
    'ProgressBar': typeof import("primevue/progressbar")['default']
    'ProgressSpinner': typeof import("primevue/progressspinner")['default']
    'NuxtPage': typeof import("../node_modules/nuxt/dist/pages/runtime/page")['default']
    'NoScript': typeof import("../node_modules/nuxt/dist/head/runtime/components")['NoScript']
    'Link': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Link']
    'Base': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Base']
    'Title': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Title']
    'Meta': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Meta']
    'Style': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Style']
    'Head': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Head']
    'Html': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Html']
    'Body': typeof import("../node_modules/nuxt/dist/head/runtime/components")['Body']
    'NuxtIsland': typeof import("../node_modules/nuxt/dist/app/components/nuxt-island")['default']
    'NuxtRouteAnnouncer': typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']
      'LazyAppHeader': LazyComponent<typeof import("../components/AppHeader.vue")['default']>
    'LazyAppSidebar': LazyComponent<typeof import("../components/AppSidebar.vue")['default']>
    'LazyLoginForm': LazyComponent<typeof import("../components/Auth/LoginForm.vue")['default']>
    'LazyClientForm': LazyComponent<typeof import("../components/Clients/ClientForm.vue")['default']>
    'LazyBudgetComparison': LazyComponent<typeof import("../components/Interventions/BudgetComparison.vue")['default']>
    'LazyInterventionCard': LazyComponent<typeof import("../components/Interventions/InterventionCard.vue")['default']>
    'LazyInterventionElectrique': LazyComponent<typeof import("../components/Interventions/InterventionElectrique.vue")['default']>
    'LazyInterventionFilters': LazyComponent<typeof import("../components/Interventions/InterventionFilters.vue")['default']>
    'LazyInterventionForm': LazyComponent<typeof import("../components/Interventions/InterventionForm.vue")['default']>
    'LazyInterventionSummary': LazyComponent<typeof import("../components/Interventions/InterventionSummary.vue")['default']>
    'LazyPhaseCard': LazyComponent<typeof import("../components/Interventions/PhaseCard.vue")['default']>
    'LazyPhaseDetailSummary': LazyComponent<typeof import("../components/Interventions/PhaseDetailSummary.vue")['default']>
    'LazyProcessTimeline': LazyComponent<typeof import("../components/Interventions/ProcessTimeline.vue")['default']>
    'LazySessionHistory': LazyComponent<typeof import("../components/Interventions/SessionHistory.vue")['default']>
    'LazyTailwindTest': LazyComponent<typeof import("../components/Test/TailwindTest.vue")['default']>
    'LazyDelayIndicator': LazyComponent<typeof import("../components/UI/DelayIndicator.vue")['default']>
    'LazyInterventionCardCompact': LazyComponent<typeof import("../components/UI/InterventionCardCompact.vue")['default']>
    'LazyMetricsCard': LazyComponent<typeof import("../components/UI/MetricsCard.vue")['default']>
    'LazyMultiSelect': LazyComponent<typeof import("../components/UI/MultiSelect.vue")['default']>
    'LazyNotificationCenter': LazyComponent<typeof import("../components/UI/NotificationCenter.vue")['default']>
    'LazyTimelineComponent': LazyComponent<typeof import("../components/UI/TimelineComponent.vue")['default']>
    'LazyTypeSelector': LazyComponent<typeof import("../components/UI/TypeSelector.vue")['default']>
    'LazyFileUpload': LazyComponent<typeof import("../components/Upload/FileUpload.vue")['default']>
    'LazyUserForm': LazyComponent<typeof import("../components/Users/UserForm.vue")['default']>
    'LazyNuxtWelcome': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/welcome.vue")['default']>
    'LazyNuxtLayout': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-layout")['default']>
    'LazyNuxtErrorBoundary': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-error-boundary.vue")['default']>
    'LazyClientOnly': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/client-only")['default']>
    'LazyDevOnly': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/dev-only")['default']>
    'LazyServerPlaceholder': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']>
    'LazyNuxtLink': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-link")['default']>
    'LazyNuxtLoadingIndicator': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-loading-indicator")['default']>
    'LazyNuxtTime': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-time.vue")['default']>
    'LazyNuxtRouteAnnouncer': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-route-announcer")['default']>
    'LazyNuxtImg': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtImg']>
    'LazyNuxtPicture': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtPicture']>
    'LazyDatePicker': LazyComponent<typeof import("primevue/datepicker")['default']>
    'LazyDropdown': LazyComponent<typeof import("primevue/dropdown")['default']>
    'LazyInputNumber': LazyComponent<typeof import("primevue/inputnumber")['default']>
    'LazyInputText': LazyComponent<typeof import("primevue/inputtext")['default']>
    'LazyPassword': LazyComponent<typeof import("primevue/password")['default']>
    'LazySelect': LazyComponent<typeof import("primevue/select")['default']>
    'LazySlider': LazyComponent<typeof import("primevue/slider")['default']>
    'LazyTextarea': LazyComponent<typeof import("primevue/textarea")['default']>
    'LazyButton': LazyComponent<typeof import("primevue/button")['default']>
    'LazyColumn': LazyComponent<typeof import("primevue/column")['default']>
    'LazyDataTable': LazyComponent<typeof import("primevue/datatable")['default']>
    'LazyCard': LazyComponent<typeof import("primevue/card")['default']>
    'LazyConfirmDialog': LazyComponent<typeof import("primevue/confirmdialog")['default']>
    'LazyDialog': LazyComponent<typeof import("primevue/dialog")['default']>
    'LazyBreadcrumb': LazyComponent<typeof import("primevue/breadcrumb")['default']>
    'LazyMegaMenu': LazyComponent<typeof import("primevue/megamenu")['default']>
    'LazyPanelMenu': LazyComponent<typeof import("primevue/panelmenu")['default']>
    'LazyMessage': LazyComponent<typeof import("primevue/message")['default']>
    'LazyToast': LazyComponent<typeof import("primevue/toast")['default']>
    'LazyAvatar': LazyComponent<typeof import("primevue/avatar")['default']>
    'LazyBadge': LazyComponent<typeof import("primevue/badge")['default']>
    'LazyProgressBar': LazyComponent<typeof import("primevue/progressbar")['default']>
    'LazyProgressSpinner': LazyComponent<typeof import("primevue/progressspinner")['default']>
    'LazyNuxtPage': LazyComponent<typeof import("../node_modules/nuxt/dist/pages/runtime/page")['default']>
    'LazyNoScript': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['NoScript']>
    'LazyLink': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Link']>
    'LazyBase': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Base']>
    'LazyTitle': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Title']>
    'LazyMeta': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Meta']>
    'LazyStyle': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Style']>
    'LazyHead': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Head']>
    'LazyHtml': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Html']>
    'LazyBody': LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Body']>
    'LazyNuxtIsland': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-island")['default']>
    'LazyNuxtRouteAnnouncer': LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']>
}

declare module 'vue' {
  export interface GlobalComponents extends _GlobalComponents { }
}

export const AppHeader: typeof import("../components/AppHeader.vue")['default']
export const AppSidebar: typeof import("../components/AppSidebar.vue")['default']
export const LoginForm: typeof import("../components/Auth/LoginForm.vue")['default']
export const ClientForm: typeof import("../components/Clients/ClientForm.vue")['default']
export const BudgetComparison: typeof import("../components/Interventions/BudgetComparison.vue")['default']
export const InterventionCard: typeof import("../components/Interventions/InterventionCard.vue")['default']
export const InterventionElectrique: typeof import("../components/Interventions/InterventionElectrique.vue")['default']
export const InterventionFilters: typeof import("../components/Interventions/InterventionFilters.vue")['default']
export const InterventionForm: typeof import("../components/Interventions/InterventionForm.vue")['default']
export const InterventionSummary: typeof import("../components/Interventions/InterventionSummary.vue")['default']
export const PhaseCard: typeof import("../components/Interventions/PhaseCard.vue")['default']
export const PhaseDetailSummary: typeof import("../components/Interventions/PhaseDetailSummary.vue")['default']
export const ProcessTimeline: typeof import("../components/Interventions/ProcessTimeline.vue")['default']
export const SessionHistory: typeof import("../components/Interventions/SessionHistory.vue")['default']
export const TailwindTest: typeof import("../components/Test/TailwindTest.vue")['default']
export const DelayIndicator: typeof import("../components/UI/DelayIndicator.vue")['default']
export const InterventionCardCompact: typeof import("../components/UI/InterventionCardCompact.vue")['default']
export const MetricsCard: typeof import("../components/UI/MetricsCard.vue")['default']
export const MultiSelect: typeof import("../components/UI/MultiSelect.vue")['default']
export const NotificationCenter: typeof import("../components/UI/NotificationCenter.vue")['default']
export const TimelineComponent: typeof import("../components/UI/TimelineComponent.vue")['default']
export const TypeSelector: typeof import("../components/UI/TypeSelector.vue")['default']
export const FileUpload: typeof import("../components/Upload/FileUpload.vue")['default']
export const UserForm: typeof import("../components/Users/UserForm.vue")['default']
export const NuxtWelcome: typeof import("../node_modules/nuxt/dist/app/components/welcome.vue")['default']
export const NuxtLayout: typeof import("../node_modules/nuxt/dist/app/components/nuxt-layout")['default']
export const NuxtErrorBoundary: typeof import("../node_modules/nuxt/dist/app/components/nuxt-error-boundary.vue")['default']
export const ClientOnly: typeof import("../node_modules/nuxt/dist/app/components/client-only")['default']
export const DevOnly: typeof import("../node_modules/nuxt/dist/app/components/dev-only")['default']
export const ServerPlaceholder: typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']
export const NuxtLink: typeof import("../node_modules/nuxt/dist/app/components/nuxt-link")['default']
export const NuxtLoadingIndicator: typeof import("../node_modules/nuxt/dist/app/components/nuxt-loading-indicator")['default']
export const NuxtTime: typeof import("../node_modules/nuxt/dist/app/components/nuxt-time.vue")['default']
export const NuxtRouteAnnouncer: typeof import("../node_modules/nuxt/dist/app/components/nuxt-route-announcer")['default']
export const NuxtImg: typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtImg']
export const NuxtPicture: typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtPicture']
export const DatePicker: typeof import("primevue/datepicker")['default']
export const Dropdown: typeof import("primevue/dropdown")['default']
export const InputNumber: typeof import("primevue/inputnumber")['default']
export const InputText: typeof import("primevue/inputtext")['default']
export const Password: typeof import("primevue/password")['default']
export const Select: typeof import("primevue/select")['default']
export const Slider: typeof import("primevue/slider")['default']
export const Textarea: typeof import("primevue/textarea")['default']
export const Button: typeof import("primevue/button")['default']
export const Column: typeof import("primevue/column")['default']
export const DataTable: typeof import("primevue/datatable")['default']
export const Card: typeof import("primevue/card")['default']
export const ConfirmDialog: typeof import("primevue/confirmdialog")['default']
export const Dialog: typeof import("primevue/dialog")['default']
export const Breadcrumb: typeof import("primevue/breadcrumb")['default']
export const MegaMenu: typeof import("primevue/megamenu")['default']
export const PanelMenu: typeof import("primevue/panelmenu")['default']
export const Message: typeof import("primevue/message")['default']
export const Toast: typeof import("primevue/toast")['default']
export const Avatar: typeof import("primevue/avatar")['default']
export const Badge: typeof import("primevue/badge")['default']
export const ProgressBar: typeof import("primevue/progressbar")['default']
export const ProgressSpinner: typeof import("primevue/progressspinner")['default']
export const NuxtPage: typeof import("../node_modules/nuxt/dist/pages/runtime/page")['default']
export const NoScript: typeof import("../node_modules/nuxt/dist/head/runtime/components")['NoScript']
export const Link: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Link']
export const Base: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Base']
export const Title: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Title']
export const Meta: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Meta']
export const Style: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Style']
export const Head: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Head']
export const Html: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Html']
export const Body: typeof import("../node_modules/nuxt/dist/head/runtime/components")['Body']
export const NuxtIsland: typeof import("../node_modules/nuxt/dist/app/components/nuxt-island")['default']
export const NuxtRouteAnnouncer: typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']
export const LazyAppHeader: LazyComponent<typeof import("../components/AppHeader.vue")['default']>
export const LazyAppSidebar: LazyComponent<typeof import("../components/AppSidebar.vue")['default']>
export const LazyLoginForm: LazyComponent<typeof import("../components/Auth/LoginForm.vue")['default']>
export const LazyClientForm: LazyComponent<typeof import("../components/Clients/ClientForm.vue")['default']>
export const LazyBudgetComparison: LazyComponent<typeof import("../components/Interventions/BudgetComparison.vue")['default']>
export const LazyInterventionCard: LazyComponent<typeof import("../components/Interventions/InterventionCard.vue")['default']>
export const LazyInterventionElectrique: LazyComponent<typeof import("../components/Interventions/InterventionElectrique.vue")['default']>
export const LazyInterventionFilters: LazyComponent<typeof import("../components/Interventions/InterventionFilters.vue")['default']>
export const LazyInterventionForm: LazyComponent<typeof import("../components/Interventions/InterventionForm.vue")['default']>
export const LazyInterventionSummary: LazyComponent<typeof import("../components/Interventions/InterventionSummary.vue")['default']>
export const LazyPhaseCard: LazyComponent<typeof import("../components/Interventions/PhaseCard.vue")['default']>
export const LazyPhaseDetailSummary: LazyComponent<typeof import("../components/Interventions/PhaseDetailSummary.vue")['default']>
export const LazyProcessTimeline: LazyComponent<typeof import("../components/Interventions/ProcessTimeline.vue")['default']>
export const LazySessionHistory: LazyComponent<typeof import("../components/Interventions/SessionHistory.vue")['default']>
export const LazyTailwindTest: LazyComponent<typeof import("../components/Test/TailwindTest.vue")['default']>
export const LazyDelayIndicator: LazyComponent<typeof import("../components/UI/DelayIndicator.vue")['default']>
export const LazyInterventionCardCompact: LazyComponent<typeof import("../components/UI/InterventionCardCompact.vue")['default']>
export const LazyMetricsCard: LazyComponent<typeof import("../components/UI/MetricsCard.vue")['default']>
export const LazyMultiSelect: LazyComponent<typeof import("../components/UI/MultiSelect.vue")['default']>
export const LazyNotificationCenter: LazyComponent<typeof import("../components/UI/NotificationCenter.vue")['default']>
export const LazyTimelineComponent: LazyComponent<typeof import("../components/UI/TimelineComponent.vue")['default']>
export const LazyTypeSelector: LazyComponent<typeof import("../components/UI/TypeSelector.vue")['default']>
export const LazyFileUpload: LazyComponent<typeof import("../components/Upload/FileUpload.vue")['default']>
export const LazyUserForm: LazyComponent<typeof import("../components/Users/UserForm.vue")['default']>
export const LazyNuxtWelcome: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/welcome.vue")['default']>
export const LazyNuxtLayout: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-layout")['default']>
export const LazyNuxtErrorBoundary: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-error-boundary.vue")['default']>
export const LazyClientOnly: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/client-only")['default']>
export const LazyDevOnly: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/dev-only")['default']>
export const LazyServerPlaceholder: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']>
export const LazyNuxtLink: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-link")['default']>
export const LazyNuxtLoadingIndicator: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-loading-indicator")['default']>
export const LazyNuxtTime: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-time.vue")['default']>
export const LazyNuxtRouteAnnouncer: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-route-announcer")['default']>
export const LazyNuxtImg: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtImg']>
export const LazyNuxtPicture: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-stubs")['NuxtPicture']>
export const LazyDatePicker: LazyComponent<typeof import("primevue/datepicker")['default']>
export const LazyDropdown: LazyComponent<typeof import("primevue/dropdown")['default']>
export const LazyInputNumber: LazyComponent<typeof import("primevue/inputnumber")['default']>
export const LazyInputText: LazyComponent<typeof import("primevue/inputtext")['default']>
export const LazyPassword: LazyComponent<typeof import("primevue/password")['default']>
export const LazySelect: LazyComponent<typeof import("primevue/select")['default']>
export const LazySlider: LazyComponent<typeof import("primevue/slider")['default']>
export const LazyTextarea: LazyComponent<typeof import("primevue/textarea")['default']>
export const LazyButton: LazyComponent<typeof import("primevue/button")['default']>
export const LazyColumn: LazyComponent<typeof import("primevue/column")['default']>
export const LazyDataTable: LazyComponent<typeof import("primevue/datatable")['default']>
export const LazyCard: LazyComponent<typeof import("primevue/card")['default']>
export const LazyConfirmDialog: LazyComponent<typeof import("primevue/confirmdialog")['default']>
export const LazyDialog: LazyComponent<typeof import("primevue/dialog")['default']>
export const LazyBreadcrumb: LazyComponent<typeof import("primevue/breadcrumb")['default']>
export const LazyMegaMenu: LazyComponent<typeof import("primevue/megamenu")['default']>
export const LazyPanelMenu: LazyComponent<typeof import("primevue/panelmenu")['default']>
export const LazyMessage: LazyComponent<typeof import("primevue/message")['default']>
export const LazyToast: LazyComponent<typeof import("primevue/toast")['default']>
export const LazyAvatar: LazyComponent<typeof import("primevue/avatar")['default']>
export const LazyBadge: LazyComponent<typeof import("primevue/badge")['default']>
export const LazyProgressBar: LazyComponent<typeof import("primevue/progressbar")['default']>
export const LazyProgressSpinner: LazyComponent<typeof import("primevue/progressspinner")['default']>
export const LazyNuxtPage: LazyComponent<typeof import("../node_modules/nuxt/dist/pages/runtime/page")['default']>
export const LazyNoScript: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['NoScript']>
export const LazyLink: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Link']>
export const LazyBase: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Base']>
export const LazyTitle: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Title']>
export const LazyMeta: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Meta']>
export const LazyStyle: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Style']>
export const LazyHead: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Head']>
export const LazyHtml: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Html']>
export const LazyBody: LazyComponent<typeof import("../node_modules/nuxt/dist/head/runtime/components")['Body']>
export const LazyNuxtIsland: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/nuxt-island")['default']>
export const LazyNuxtRouteAnnouncer: LazyComponent<typeof import("../node_modules/nuxt/dist/app/components/server-placeholder")['default']>

export const componentNames: string[]
