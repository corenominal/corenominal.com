# AI Chat — Favicon & PWA Icon Files

This archive was generated with the [Favicon & PWA Icon Generator](https://favicons-pwa.philipnewbrough.co.uk).

## Files Included

| File | Purpose |
|------|---------|
| `favicon.ico` | Multi-size ICO (16×16 & 32×32) for legacy browser support |
| `icon-16x16.png` | Small favicon for browser tabs |
| `icon-32x32.png` | Standard favicon for most browsers |
| `icon-48x48.png` | General-purpose icon |
| `icon-64x64.png` | General-purpose icon |
| `icon-96x96.png` | Android Chrome (older) |
| `icon-128x128.png` | Chrome Web Store icon |
| `icon-144x144.png` | Windows pinned-site tile |
| `icon-152x152.png` | iPad Retina touch icon |
| `apple-touch-icon.png` | iOS / macOS Safari home screen icon (180×180) |
| `icon-192x192.png` | Android Chrome home screen icon |
| `icon-256x256.png` | High-resolution general icon |
| `icon-512x512.png` | PWA splash screen & maskable icon |
| `screenshot-mobile.png` | PWA install prompt screenshot — narrow (390×844) |
| `screenshot-wide.png` | PWA install prompt screenshot — wide (1280×800) |
| `manifest.json` | Web App Manifest |

## Deployment

Copy all files to the root of your web server, alongside your `index.html`.

## HTML `<head>` Tags

Add the following inside the `<head>` element of your HTML:

```html
<!-- Favicon -->
<link rel="icon" href="favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="icon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="icon-16x16.png">

<!-- Apple Touch Icon -->
<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">

<!-- Web App Manifest -->
<link rel="manifest" href="manifest.json">

<!-- Theme Colour -->
<meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
```

## manifest.json

The `manifest.json` file tells browsers how your web app should appear and behave when installed. Key fields:

- **name** — Full app name shown on install prompts and splash screens.
- **short_name** — Short label shown beneath the home screen icon.
- **display** — Controls browser chrome when launched. `standalone` hides the browser UI.
- **theme_color** — Colour of the browser toolbar / status bar.
- **background_color** — Background colour shown on the splash screen while the app loads.
- **icons** — Icon files used for the home screen, app switcher, and splash screen.
- **screenshots** — Preview images shown in the PWA install prompt (where supported).

## Notes

- All icon files should sit in the same directory as `manifest.json` and your HTML. If you move them to a sub-folder, update the `src` paths in `manifest.json` and the `href` values in your HTML accordingly.
- `screenshot-mobile.png` and `screenshot-wide.png` are placeholder screenshots generated from your icon. Replace them with real screenshots of your app for a better install experience.
- `apple-touch-icon.png` is listed in `manifest.json` under a separate `"purpose": "any"` entry so it is displayed as-is without masking or cropping.
