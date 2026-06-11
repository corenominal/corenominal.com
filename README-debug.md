# corenominal.com - Debug

## Overview

The debug system is a set of admin-only controllers, views, and a routing shim that expose diagnostic information about the running application. It is not a general-purpose logging framework — its sole purpose is to make it easy to add and browse ad-hoc diagnostic endpoints without configuring individual routes.

---

## Access Control

All `/debug` routes are protected by `DebugFilter` — [app/Filters/DebugFilter.php](app/Filters/DebugFilter.php).

The filter runs after `SessionFilter` has hydrated the session. It redirects to `/auth` if the user is not authenticated, or to `/unauthorised` if the user is not an administrator. The filter is applied in [app/Config/Filters.php](app/Config/Filters.php):

```
'debugfilter' => ['before' => ['debug', 'debug/*']],
```

There is no publicly accessible debug output at any time.

---

## Routes

Routes are grouped under `/debug` in [app/Config/Routes.php](app/Config/Routes.php).

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/debug` | `Debug\Home::index` | Lists available debug controllers |
| GET | `/debug/(:segment)` | `Debug\Rerouter::reroute/$1` | Loads a controller by name and calls `index()` |
| GET | `/debug/(:segment)/(:segment)` | `Debug\Rerouter::reroute/$1/$2` | Loads a controller by name and calls a specific method |

Routing is intentionally open-ended: adding a new controller class to `Controllers/Debug/` makes it immediately available without touching the route config.

---

## Architecture

### `Debug\Home` — [app/Controllers/Debug/Home.php](app/Controllers/Debug/Home.php)

The index/landing page. Reads all filenames from `Controllers/Debug/`, strips `Home.php`, `BaseController.php`, and `Rerouter.php` from the list, then renders a filterable link list pointing to each controller. The view is [app/Views/debug/home.php](app/Views/debug/home.php).

### `Debug\BaseController` — [app/Controllers/Debug/BaseController.php](app/Controllers/Debug/BaseController.php)

All diagnostic controllers extend this class. It provides:

- A shared `$session` property.
- A default `index()` method that introspects the subclass with `get_class_methods()`, filters out framework and infrastructure methods, and renders a link list of the remaining public methods via [app/Views/debug/methods.php](app/Views/debug/methods.php).

This means any controller that only needs `index()` to list its own methods gets that behaviour for free without overriding anything.

### `Debug\Rerouter` — [app/Controllers/Debug/Rerouter.php](app/Controllers/Debug/Rerouter.php)

The routing shim that maps URL segments to controller classes and methods. Given `/debug/session/show_session_data`:

1. Uppercases the first segment → `Session`.
2. Resolves the fully-qualified class `App\Controllers\Debug\Session`.
3. Throws `PageNotFoundException` if the class or method does not exist.
4. Instantiates the class and calls the method (or `index()` if no method segment).

This is the only place that translates URL segments into controller dispatch — no individual route entries are needed per diagnostic.

---

## Views

| Path | Used by | Purpose |
|---|---|---|
| [app/Views/debug/home.php](app/Views/debug/home.php) | `Debug\Home` | Filterable list of available controllers |
| [app/Views/debug/methods.php](app/Views/debug/methods.php) | `Debug\BaseController::index` | List of public methods on a controller |
| [app/Views/debug/default.php](app/Views/debug/default.php) | All diagnostic methods | Breadcrumb + `var_dump` of `$dump`; also renders `$html` inside a bordered container if set |

---

## Client-side Scripts

| Path | Loaded by | Purpose |
|---|---|---|
| [public/assets/js/debug/debug.js](public/assets/js/debug/debug.js) | All debug views | Marks the `/debug` sidebar link as active |
| [public/assets/js/debug/debug-home.js](public/assets/js/debug/debug-home.js) | `debug/home` view | Live filter on the controller list — hides non-matching items as the user types |

---

## Adding a New Diagnostic

1. Create a new class in [app/Controllers/Debug/](app/Controllers/Debug/) extending `Debug\BaseController`.
2. Add one or more public methods. Pass `$data['dump']` to the `debug/default` view to display output.
3. That is all. The controller appears automatically in the `/debug` home list, and its methods are reachable at `/debug/{classname}/{method_name}`.

No route changes are required.

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Debug/Home.php](app/Controllers/Debug/Home.php) | Landing page — lists available controllers |
| [app/Controllers/Debug/BaseController.php](app/Controllers/Debug/BaseController.php) | Base class — shared session, auto `index()` method list |
| [app/Controllers/Debug/Rerouter.php](app/Controllers/Debug/Rerouter.php) | URL-to-class dispatch shim |
| [app/Filters/DebugFilter.php](app/Filters/DebugFilter.php) | Admin-only access gate |
| [app/Views/debug/home.php](app/Views/debug/home.php) | Controller list view |
| [app/Views/debug/methods.php](app/Views/debug/methods.php) | Method list view |
| [app/Views/debug/default.php](app/Views/debug/default.php) | Output dump view |
| [public/assets/js/debug/debug.js](public/assets/js/debug/debug.js) | Sidebar active-link highlight |
| [public/assets/js/debug/debug-home.js](public/assets/js/debug/debug-home.js) | Controller list filter |
