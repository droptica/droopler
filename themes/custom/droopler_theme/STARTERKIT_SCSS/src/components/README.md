# How to maintain the components directory

## Quick start
For a quick start you may copy/paste all the components or those you need from
the parent drupal_theme theme under `drupal_theme/src/components` into this
directory.

## Extend the existing component library
If you don't need to change the component markup but you want to override the
default styling or the JS behavior add the SCSS/JS file with the same name
as the component you need to extend in the `extends` subdirectory.

> For example:
> - `components/extends/d-p-banner/d-p-banner.scss`

The base component will be extended and those files will be added to the library
definition.

## Override existing component
If you need to override the component markup with the styling or JS behavior,
copy the entire component directory from the `droopler_theme` to the components
directory in the subtheme.

> For example:
> - `components/heading/heading.twig`
> - `components/heading/_heading.scss`

You don't have to copy the SCSS/JS file if you don't need to change this
behaviors. In this case, you can still attach the library from the base theme by
leaving the line `{{ attach_library('droopler_theme/heading') }}` unchanged.

## Creating new component
Create the new directory with the name of the component and all need files.

Define the markup in the TWIG file:
- `components/component_name/component_name.twig`

Define the styling in the SCSS file:
- `components/component_name/component_name.scss`

Define the behavior in the JS file:
- `components/component_name/component_name.js`

The library for the component will be automatically created. You should only
attach it at the beginning of your TWIG file:
- `{{ attach_library('droopler_subtheme/component_name') }}`
