--------------------------------------------------------------------------------
Taxonomy Views Integrator Overview

Taxonomy Views Integrator allows selective overriding of taxonomy terms and/or
vocabulary with the view of your choice.

Using views and taxonomy alone, one can already override a taxonomy term, or 
even all terms in all vocabulary. But a view that overrides just the terms in
vocabulary and is not possible without Taxonomy Views Integrator.

--------------------------------------------------------------------------------

Usage scenarios:

Lets say you have a vocabulary defined for events, news items, and blogs.  If 
you want to have a calendar view for all term displays in events, one view for 
all news item terms, and you want to have standard taxonomy displays for blogs, 
this is where TVI shines.

In Drupal 8, taxonomy_term_page was replaced by a View display provided by core.

However, if you wanted to display different Views for different terms or vocabs,
you still need Taxonomy Views Integrator. That way, you can create a couple of view
displays without configuring tricky attachments or endless context argument filter
validation rules.

--------------------------------------------------------------------------------

Installing Taxonomy Views Integrator

1: Enable Taxonomy Views Integrator module (requires core Taxonomy and Views)

2: Define a new view or clone taxonomy/term/* view.

3: After you know what view you want to use on a vocab or term, simply visit
   the term or vocabulary edit page that the view should be applied to, select
   the view that you wish to use using the drop-down select list, select the
   view plugin, and save your changes.

   For vocabulary, be sure to tick off "Child terms will use these settings" so terms
   will be overridden immediately.

--------------------------------------------------------------------------------

Theming

Since TVI leverages Views to inject into term pages, you can override output from
that level. Check your View display options for theme suggestions and other settings.

--------------------------------------------------------------------------------

1: TVI cannot currently deal with multiple term displays.
   ex. taxonomy/term/4+6+7 and will pass these requests to non-TVI views if
   they exist, or taxonomy if all else fails.

2: TVI does not care what your view does however TVI will pass the term id and
   term id with depth modifier to the view as arguments.  To make use of these,
   simply add the following arguments to the view you plan to use on your term
   or vocabulary:

   A1: Taxonomy: Term ID (with depth)
   A2: Taxonomy: Term ID depth modifier

3: TVI has an order of precedence mechanism:

   1: TVI term view override
   2: TVI vocabulary view override
   3: view path: taxonomy/term/tid(s) (exact match) +
   4: view path: taxonomy/term/* +
   5: taxonomy: taxonomy/term/tid(s) ++

4: You may clone the default taxonomy/term/* view to create your TVI views as
	 their arguments are identical. However, it is a good idea to give all TVI
	 views that provide page displays a path other than the default
	 taxonomy/term/*. Alternatively, you may remove the page displays and simply
	 use a block view for TVI views: this is recommended. Although, with a page display,
	 you can change the page title in the View to be something other than the term name itself.

--------------------------------------------------------------------------------

+ If there are no views specified to be used on the viewed term or vocabulary,
  TVI will seek to find a view defined for the requested path.

++ In the case that TVI finds no views (TVI active or otherwise) for this term
   display, TVI will pass the buck to taxonomy for the regular display.

   
