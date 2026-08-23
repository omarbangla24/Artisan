# ARTISAN Chartered Accountants — website

A static rebuild of https://artisan-ca.net/ with the same content and a new,
modern design. No WordPress, no build dependencies — plain HTML, one CSS file,
one JS file.

## Design

Clean and photography-led: a full-bleed hero photo with the headline over it,
generous white space, hairline rules instead of heavy cards, circular arrow CTAs,
and image cards for the service divisions.

**Brand colour** is taken from the firm logo: **#1961A9** (91% of the logo's
pixels) with black. All colours are CSS custom properties at the top of
`assets/css/style.css` (`--brand`, `--night`, `--sand`, `--ink`, …) — change them
there and the whole site follows.

Type: **Plus Jakarta Sans** (headings) + **Inter** (body), loaded from Google Fonts.

### Hero image

The hero uses `assets/img/single-servce1.jpg`, which came from the old site at
870&times;426 — usable, but soft on large screens. Drop in a photo of at least
2400px wide (the firm's own office or team is ideal) and update the `<img>` in
`src/pages/index.html`.

### Hero animation

The hero carries a live accounting chart (CSS keyframes + a little JS, no library):
bars grow on staggered delays, the trend line draws itself with an area fill,
value chips fade in, and the KPI figures count up. The loop runs on a 7.2s cycle.
Everything freezes flat for visitors who prefer reduced motion.

Bar values are the `--h` (height) and `--d` (delay) custom properties on each
`.bar` in `src/pages/index.html`; KPI figures use `data-count`.

## Structure

```
index.html … sitemap.html   generated pages (deploy these)
assets/css/style.css        design system
assets/js/main.js           nav, hero slider, accordions, modal, forms, reveals
assets/img/                 logo, partner photos, client logos, article images
src/layout.html             shared shell: topbar, header, drawer, CTA, footer, modal
src/pages/*.html            page content fragments
build.py                    injects fragments into the layout
```

### Editing

Edit `src/pages/<page>.html` (content) or `src/layout.html` (header/footer/modal),
then regenerate:

```bash
python3 build.py
```

Each fragment starts with a small metadata comment:

```html
<!--
title: Page title
description: Meta description
nav: about          (highlights that main-nav item)
output: about.html  (optional; defaults to the fragment filename)
-->
```

You can also edit the generated `.html` files directly if you prefer not to use
the build step — but a later `build.py` run overwrites them.

### Local preview

```bash
python3 -m http.server 4173
```

## Pages

Home · About Firm · Our Vision · Our Mission · Our Teams (+ 6 partner profiles) ·
Services · Resources (+ 2 articles) · Contact · Privacy Policy ·
Terms & Conditions · Terms of Reference · Sitemap · Team Login · Team Registration

## Forms — important

The site is static, so there is no server to receive submissions. Current behaviour:

- **Contact / quotation / newsletter forms** compose the entered values into an
  email to `info@artisan-ca.com` and open the visitor's mail client.
- **Team login / registration** do nothing and show a notice — they are marked
  `data-no-mail`, and passwords and file uploads are never placed in an email.

To connect a real backend, replace `handleSubmit()` in `assets/js/main.js` with a
`fetch()` POST to your endpoint (PHP mailer, Formspree, Netlify Forms, …). The
recipient address is the `MAIL_TO` constant at the top of that function.

Every form includes a hidden honeypot field (`.hp-field`) for basic bot filtering.

## Notes

- Images were downloaded from the live site and renamed (`client-*`, `partner-*`,
  `post-*`). Replace them in `assets/img/` keeping the same filenames.
- The old site's top utility bar was removed for a cleaner header; its links
  (team login/registration, phone, email) now live in the footer and the mobile menu.
- Responsive down to 360px; respects `prefers-reduced-motion`; print stylesheet included.
- `AccountingService` JSON-LD, canonical URLs and Open Graph tags are in `src/layout.html`.
