# Design Versions & Public Switcher

Notes for the multi-design effort on blog.creativebymelissa.com. This file is
documentation only — it does not affect the running site.

## Goal

Let public (logged-out) visitors switch between distinct design versions of the
blog from a small switcher in the site header. New versions are added over time
and "plug in" to the switcher.

## Decisions (locked)

- **Versions = scoped skins.** Shared content/templates; each version is a
  self-contained CSS bundle activated by a `design--{slug}` class on `<body>`.
  A version may also rearrange layout/sections where needed.
- **Switcher UI:** a row of labeled pills in the site header, each pill with a
  distinct icon.
- **Persistence:** session-only & client-side. No cookie, no storage, no
  cache-vary — switching swaps the body class in JS; a reload returns to the
  default version.
- **Default version:** the newest version (first entry in the registry).
- **Colour rule:** never use pink or red in any version. Accent palette skews
  teal / indigo / blue / lime-green / amber-gold.

## How it works (live)

- `web/app/mu-plugins/design-versions.php` — always-on plugin. Registers the
  versions (newest first), sets the default `design--{slug}` body class,
  enqueues each version's scoped CSS bundle, injects the header pill switcher
  after the Site Title block, prints the Berry backdrop layer, and ships the
  client-side switch script.
- `web/app/themes/twentytwentyfive-pixel/assets/designs/{slug}/{slug}.css` —
  one scoped bundle per version (Pixel needs none; the theme's own style.css
  IS Pixel).

## Adding a new version

1. Prototype as HTML, get sign-off.
2. Drop `assets/designs/{slug}/{slug}.css` (everything scoped under
   `body.design--{slug}`).
3. Add one entry to `CBM_Design_Versions::versions()` (newest goes first to
   become the default). Add an icon `<symbol>` to `sprite()` if needed.

## Current versions

- **berry** (default/newest) — dark teal/indigo abstract, gem-strawberry,
  blueprint backdrop, sharp dashed edges, per-post accent colours.
- **pixel** — the original light pixel-art look (base theme style.css).

## Status

- [x] Prototype harness with switcher + Berry/Pixel skins
- [x] mu-plugin migration (switcher + registry)
- [x] Berry scoped CSS bundle
- [ ] Verify on live site / staging and fine-tune selectors against real markup
