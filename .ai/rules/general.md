---
paths:
  - composer.json
---

# General

## Artisan npm installs time out at 60s
Artisan scaffold commands that install Node deps (install:broadcasting, install:api, etc.) hardcode a 60s timeout for the npm step and fail on this machine (npm install + vite build take longer). Workaround: let the command fail at the npm step, then run the npm portion manually: `npm install --save-dev <pkgs> --ignore-scripts && npm run build`. The rest of the command (env keys, config publish, app.tsx wiring) completes before the timeout, so re-running is unnecessary.
