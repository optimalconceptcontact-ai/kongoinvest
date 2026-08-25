---
paths:
  - 'resources/js/**'
---

# Js

## Lowercase import paths for @/ aliases
Import paths must use lowercase directory names exactly as on disk: '@/components/...', '@/layouts/...', '@/hooks/...' — never '@/Components/...', '@/Layouts/...', '@/Hooks/...'. Windows resolves case-insensitively (dev works anyway) but tsc --noEmit fails with TS1149 and Linux/CI breaks. Run `npm run types:check` after frontend changes. Also: pages/Settings uses PascalCase filenames (Kyc.tsx, Profile.tsx...) matching the Inertia component names rendered by controllers; lowercase duplicates (security.tsx, appearance.tsx) are dead Breeze leftovers.

## useForm transform() returns void in @inertiajs/react 3.6
In @inertiajs/react v3.6.1 `transform` is a setter that returns void — call it separately, then submit: `transform((d) => ({...})); put('/url');`. Do NOT chain `transform(...).put(...)` (TS2339: Property 'put' does not exist on type 'void') and do NOT pass `transform` inside the submit options object (TS2353).

## Fortify /user/* routes: PUT only, not PATCH
Fortify routes are method-specific and fail with 405 MethodNotAllowed on the wrong verb: /user/profile-information is PUT only, /user/password is PUT only, /user/confirm-password is POST. Submit profile updates with `put()`, never `patch()` (Settings/Profile.tsx).

## Animations framer-motion : presets centralisés dans @/lib/animations
Style maison « doux mais rapide » : durées 0.12–0.35s, easeOut/easeSoft, ressorts stiffness ~320 damping ~30 sans rebond, distances 6–10px. Toujours importer fadeIn/fadeUp/scaleIn/staggerContainer/staggerItem depuis '@/lib/animations' au lieu de redéfinir des variants. MotionConfig reducedMotion="user" est déjà dans root-layout — ne pas le doubler. N'animer que opacity/transform : pas de width/height (les tests lisent style.width statique, ex. barre ProjectCard animée en scaleX). Transitions de page : composant PageTransition dans chaque layout (Inertia remonte la page à chaque visite, pas besoin d'AnimatePresence).
