core = 8.x
api = 2
defaults[projects][subdir] = contrib

projects[admin_toolbar][version] = 2
projects[advagg][version] = 4
projects[better_exposed_filters][version] = 3
projects[checklistapi][version] = 1
projects[colorbox][version] = 1
projects[config_update][version] = 1
projects[contact_formatter][version] = 1
projects[ctools][version] = 3
projects[entity_reference_display][version] = 1
projects[entity_reference_revisions][version] = 1
projects[facets][version] = 1
projects[features][version] = 3
projects[field_group][version] = 3

; Geysir module - tag to the specific commit hash on dev branch and include droopler merged patches for geysir.
projects[geysir][type] = module
projects[geysir][download][type] = git
projects[geysir][download][revision] = 636a14e8003ccb0ef3df6370abc135acfa59eedf
projects[geysir][download][branch] = 8.x-1.x
projects[geysir][patch][] = https://www.drupal.org/files/issues/2020-05-05/droopler-geysir-issues-3133680-2.patch

projects[google_analytics][version] = 2
projects[google_tag][version] = 1
projects[lazy][version] = 3
projects[lazy][patch][] = https://www.drupal.org/files/issues/2020-04-17/lazy_droopler.patch
projects[link_attributes][version] = 1
projects[linkit][version] = 4
projects[metatag][version] = 1
projects[paragraphs][version] = 1
projects[pathauto][version] = 1
projects[redirect][version] = 1
projects[schema_metatag][version] = 1
projects[search_api][version] = 1
projects[simple_sitemap][version] = 3
projects[smtp][version] = 1
projects[svg_image][version] = 1
projects[token][version] = 1
projects[tvi][version] = 1
projects[we_megamenu][version] = 1
