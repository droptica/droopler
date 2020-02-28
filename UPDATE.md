## Restoring old color scheme ##
In case you've grown accustomed to old Droopler color scheme, we provide you with a file containing
SCSS variables from the older Droopler builds. This file is located in **/themes/custom/droopler_theme/scss/config/_old_color_scheme.scss**

In order to restore old color scheme either copy it's contents into the  **/themes/custom/droopler_theme/scss/config/_color.scss**,
or modify **/themes/custom/droopler_theme/scss/config/_all.scss** like this:

```scss (scss/config/_all.scss)

@import "layout";
@import "colors"; // Remove this line
@import "fonts";
@import "paths";
@import "bootstrap-overrides";

@import "layout";
@import "old_color_scheme"; // Add this new line
@import "fonts";
@import "paths";
@import "bootstrap-overrides";

```

After compiling SCSS, you can enjoy Droopler's 2.0 new functionalities while retaining the old color scheme.
In case you modified default Droopler color scheme you might want to use this old color scheme file as a guideline to moving your color scheme onto 2.0
