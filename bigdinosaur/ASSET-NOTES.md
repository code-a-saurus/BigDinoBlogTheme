# Asset maintenance notes

The CSS and JavaScript sources carry short comments where a rule is not
self-evident; the longer reasoning is collected here so the sources stay
readable. Keep these notes current when you change the assets.

Two things to know about how the files are published. `style.css` opens with
the theme metadata header WordPress requires; the bigdinosaur-static plugin
drops that header from its compiled stylesheet, points the font URLs at
`/fonts/`, maps the `wp-block-*` class names to its own vocabulary and
publishes everything else as written; `post-list.js` it copies byte for byte.
The plugin refuses either file if the result contains `wp-`, `wp_` or
`WordPress`, so keep those strings out of both (`editor.css` is never
published and is exempt). Both Prism files open with
the full PrismJS MIT notice (copyright line and permission text), because MIT
requires that notice to accompany every copy. The same text is in
`LICENSES/PrismJS-MIT.txt` beside the font licenses.

## Stylesheet and editor

- Crimson Pro is the reading face and, at weight 500, the site title.
  Michroma is the instrument face for uppercase post/archive titles, h2/h3,
  drop caps and small tracked labels. Jost covers h4–h6 and captions; Plex
  Mono covers code. Small instrument labels are Michroma 11px, uppercase.
- Font fallbacks use size-adjust and ascent/descent overrides from
  `../tools/font-metrics.py`. Regenerate the metrics if a font file changes.
  Georgia/Arial/Menlo cover macOS, iOS and Windows; Noto Serif/Roboto cover
  Android, where local() does not follow OS aliases. Serif metrics are
  matched at body weight 200. Michroma's metrics are measured on mixed-case
  title text rather than on the uppercase rendering the theme applies. The
  fallback aims to preserve line wraps.
- Light is the CSS default. The head script sets `html[data-scheme]` from
  localStorage or the OS; without JavaScript, CSS follows the OS.
- The masthead is a two-column grid with no tagline row. The mascot/title
  row spans both columns; navigation sits left and the toggle right. Crimson
  Pro's 0.573em cap height at 84px matches Michroma's 0.75em at 64px. The
  title at weight 500 spans about 6.64em (558px); `min()` leaves room for the
  120px mascot allocation and 15px of scrollbar slack.
- Navigation labels do not wrap: gaps shrink from 40px to 1em before type
  shrinks below 13px. The six labels span about 30.4em; the 36em budget adds
  five gaps, beside the roughly 150px toggle and its 24px margin above 760px.
  Padding of 15px above and 13px below gives a 48px strip. Hover and
  `a[aria-current="page"]` use the same 2px accent on the header rule.
  WordPress sets `aria-current="page"` on the current menu item itself, so
  one selector serves both the editor site and the static site.
- Intro, excerpts and singular content use the full container width. Intro
  bottom spacing mirrors the footer's top; post-title spacing clears drop
  caps in Blink. Read-more links sit below a drop cap taller than a short
  excerpt. `overflow-anchor: none` on navigation, status and footer prevents
  appended posts dragging the view.
- Drop caps use Michroma. Core supplies the float and uppercase rules on
  WordPress; the static plugin's `blocks.css` supplies the equivalent base
  rules on the static site. Theme sizes and margins account for Michroma's
  wide caps, with smaller caps on phones.
- Images, galleries and embeds get typography and spacing here. Alignment
  comes from core CSS on WordPress and from the plugin's `blocks.css` on the
  static site. Quotes use italic body type and a hanging Michroma `>`
  marker, with no panel or rule. The matching right indent drops below 760px.
- Pullquotes stay full-width regardless of alignment. Michroma has one
  weight and no italic: em/strong are flattened; strong takes the accent
  color. `--quote-mark-x` places the small quote disc on the top rule (50%
  centered, 33.33% for a left-third position); its page-color fill breaks the
  rule. Code uses Okaidia in both schemes; Prism colors code-tagged posts.
- The footer gutter is a margin so its rule spans exactly the content column.
  On narrow screens the toggle moves above the title, still right-aligned;
  a smaller mascot leaves roughly 23px title type at 400px and navigation
  gets the full column.
- `assets/css/editor.css` mirrors fonts, colors and scale in the editor.
  The classic editor canvas wraps aligned blocks in
  `div.wp-block[data-align=…]`, sets the wrapper height to zero and floats
  its child. Because this theme cancels the figure's float, the stylesheet
  restores the wrapper height so the next paragraph does not flow
  underneath. Core centers blocks with auto horizontal margins; the
  `2em auto` spacing on images and quotes matches that.

## Post-list JavaScript

`assets/js/post-list.js` progressively enhances the Older posts link. An
IntersectionObserver starts loading 800px early. Fetched articles keep their
server-rendered excerpts, drop caps and read-more links. Their HTML is trusted
same-origin site output; this script is not a sanitizer.

The markers track the first article of each fetched page. As articles cross
the top half of the viewport, history.replaceState updates the URL so reload
and Back land near the same content. An article whose ID is already on the
page is skipped, because publishing moves page boundaries and consecutive
pages can overlap. Re-observing the navigation after an append checks whether
it is still near the viewport. No next link ends observation.

Plain left-clicks load in place. Modified clicks, clicks while loading, a
failed fetch, or disabled JavaScript preserve ordinary navigation. A response
without a post list (for example an interstitial) fails. Keep this fallback.

`loadStyles()` exists for the WordPress site itself, where core can print
block CSS outside the articles (`style[id]` and `link[id]` in the head) that
a later page's blocks need. Styles the fetched page has and this one lacks are
inserted in their original cascade order, block defaults before theme
overrides. A changed stylesheet URL, a style id reused for different rules, or
a new rule for a selector that already has one falls back to ordinary
navigation: the script refuses to interpret arbitrary CSS or overwrite an
earlier layout. Linked sheets must finish loading before the articles appear;
a timeout or load error also falls back. The static site has one compiled
stylesheet, so none of this runs there.

## Prism provenance

Both Prism files open with the full MIT notice described above; keep it intact
when rebuilding either file. The static plugin copies both files byte for
byte, notice included.

`assets/js/prism.js` is PrismJS 1.30.0 (MIT), rebuilt from the npm tarball's
minified components in dependency order. Its language set is: markup, css,
clike, javascript, bash, c, cpp, css-extras, dns-zone-file, ini, latex,
markup-templating, nginx, php, plsql, powershell, ruby, sql, yaml. The
equivalent download-page URL is:

https://prismjs.com/download.html#themes=prism-okaidia&languages=markup+css+clike+javascript+bash+c+cpp+css-extras+dns-zone-file+ini+latex+markup-templating+nginx+php+plsql+powershell+ruby+sql+yaml

`assets/css/prism.css` is the Okaidia theme stylesheet with one change: the
font-family is the site's `var(--mono)` stack. Okaidia is by **ocodia**,
loosely based on the Monokai TextMate theme by http://www.monokai.nl/.
