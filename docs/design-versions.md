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
  distinct pixel icon.
- **Persistence:** session-only. No cookie, no storage — a reload returns to the
  default version. (Keeps page caching simple; nothing to vary on.)
- **Default version:** the newest design we build becomes the default.
- **Baseline:** the current `twentytwentyfive-pixel` look is the "Pixel" version.

## Pipeline

1. Prototype each version as HTML in the design tool (preview / QA / sign-off).
2. On approval, migrate into this repo:
   - structural HTML  -> block templates / template parts
   - each skin        -> scoped CSS bundle (`design--{slug}`)
   - switcher logic   -> a small always-on mu-plugin that registers versions,
     resolves the active one per request (session-only), adds the body class,
     enqueues that version's assets, and renders the header pills.

## Planned layout (subject to change)

```
web/app/mu-plugins/
  design-versions/              # registry + switcher (always-on)
web/app/themes/twentytwentyfive-pixel/
  assets/designs/{slug}/        # per-version css/js
```

## Status

- [x] Prototype harness with Pixel baseline + header switcher
- [ ] First new design direction
- [ ] mu-plugin migration
