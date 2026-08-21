<?php

// Fortify registers /login, /register, /forgot-password, /reset-password,
// /email/verify, /user/confirm-password, and /two-factor-challenge itself
// (config/fortify.php + app/Providers/FortifyServiceProvider.php). We do
// not redefine them here — see spec §5 ("NE PAS réimplémenter
// manuellement toute l'authentification").
//
// Because auth is presented as React modals rather than dedicated pages
// (spec §6), there are no corresponding GET view routes in this file.
// FortifyServiceProvider binds the response contracts so a completed
// login/register simply redirects back into the app.
