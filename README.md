# London TfL Route Planner V11

V11 is based on the supplied working V10.

## New
- Large, very low-opacity TfL-inspired background watermark.
- TfL credentials removed from browser-side JavaScript.
- Nearby-station TfL calls now go through `api/nearest-stations.php`.
- The PHP proxy reads `TFL_APP_KEY` from the VPS environment and adds it server-side.
- No real credential is included in this package.

## Deployment

Recommended public directory:

```
public_html/
├── index.html
├── api/
│   └── nearest-stations.php
└── .env.example
```

Set the real credential as a server environment variable:

```
TFL_APP_KEY=YOUR_REAL_KEY
```

Do NOT put the real key in `index.html`, JavaScript, HTML, CSS, or a public `.env` file.

The browser calls:

```
/api/nearest-stations.php?lat=...&lon=...
```

The PHP server calls TfL and appends the secret `app_key`.

## Important
A browser-only static app cannot keep an API key secret. If a credential is embedded in JavaScript, users can inspect it in DevTools/network requests. The V11 architecture removes that exposure by using a server-side proxy.

The optional TFL_APP_SECRET is not sent to the browser and is not used by the StopPoint request unless your specific TfL service requires it.
"# tfl_train" 
