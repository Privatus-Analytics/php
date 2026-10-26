# Privatus Analytics for PHP

**Real visitor numbers for your PHP app. No cookies, no cookie pop-up, no bots.**

You want to know if people use your PHP app. But Google Analytics uses cookies,
so in many countries you must show a cookie pop-up first (the box that asks
visitors to say yes to cookies). Many people click no, so they never show up in
your numbers. Bots add fake visits on top.

This package adds the Privatus Analytics script to your PHP app. Laravel gets
Blade directives, a Blade component and a middleware. Symfony and other Twig
apps get a Twig extension. The script sets no cookies and keeps no IP addresses.
Bots are filtered out before they are counted.

So you see how many real people came, which pages they looked at and where they
came from, on one simple dashboard. A report comes to your inbox each week, and
you can set alerts for when traffic jumps or drops. And you don't need a cookie
pop-up for your analytics.

**Free plan, no card:** 10,000 events a month (an event is a page view or an action you track) on up to 3 sites. [Start free at privatusanalytics.com](https://privatusanalytics.com).

- **No cookies.** No cookies and no hidden IDs on your visitors' devices, and no IP
  addresses are kept. Laws differ by country, so check your own legal advice.
- **Real people, not bots.** Known bots, data centers and spam visits are left
  out on every plan.
- **Small and fast.** The script is about 3 KB when compressed.

## What this package does

- Works with plain PHP.
- A Laravel package: Blade directives, a Blade component and an optional
  middleware.
- A Twig extension for Symfony and other Twig apps.
- The package only prints HTML: the browser script tag and some HTML
  attributes. It never sends events from your server, sets no cookies, and
  never reads or stores IP addresses or other details about visitors.

You need a Privatus Analytics account to see your reports, and there is a
free plan. [Sign up at privatusanalytics.com](https://privatusanalytics.com).

## Requirements

* PHP 8.1 or later.
* For Laravel: Laravel 12 or 13 (they need PHP 8.2 or later).
* For Twig: Twig 3.
* A Privatus Analytics site id (it starts with `pa_`). You find it under
  Site settings, Tracking.

## Install

```sh
composer require privatus/analytics
```

For the full setup guide and every tracker option, see the [Privatus
Analytics docs](https://privatusanalytics.com/docs).

Set your site id with an environment variable:

```sh
PRIVATUS_SITE_ID=pa_YOURSITEID
```

## Add the tag

### Laravel

Laravel finds the package on its own. Put the directive in the `<head>` of
your layout, for example `resources/views/layouts/app.blade.php`:

```blade
<head>
    <title>My app</title>
    @privatusAnalytics
</head>
```

Or use the component:

```blade
<x-privatus-analytics />
```

In production it prints:

```html
<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_YOURSITEID"></script>
```

The tag prints nothing when there is no site id, and nothing outside the
`production` environment (see below). If your app uses a Content Security
Policy nonce (a one-time code that lets a script run) through
`Vite::useCspNonce()`, the tag adds it for you.

To change the settings, publish the config file:

```sh
php artisan vendor:publish --tag=privatus-config
```

That creates `config/privatus.php`.

### Symfony and other Twig apps

Register the Twig extension as a service in `config/services.yaml`:

```yaml
parameters:
    env(PRIVATUS_SITE_ID): ''

services:
    Privatus\Analytics\Config:
        arguments:
            $siteId: '%env(PRIVATUS_SITE_ID)%'
            $environment: '%kernel.environment%'
            $enabledEnvironments: ['prod']

    Privatus\Analytics\Twig\PrivatusExtension:
        arguments:
            $config: '@Privatus\Analytics\Config'
        tags: ['twig.extension']
```

Symfony names its production environment `prod`, so set
`$enabledEnvironments` as above. The `parameters` line stops Symfony from
failing when the variable is not set (the tag then prints nothing). With `autoconfigure: true` (the Symfony
default) you can leave out the `tags` line. Then, in your base template:

```twig
<head>
    <title>My app</title>
    {{ privatus_analytics() }}
</head>
```

For a Content Security Policy nonce, pass it in. With NelmioSecurityBundle:

```twig
{{ privatus_analytics({nonce: csp_nonce('script')}) }}
```

Outside Symfony, add the extension yourself:

```php
$twig->addExtension(new \Privatus\Analytics\Twig\PrivatusExtension(
    new \Privatus\Analytics\Config(siteId: 'pa_YOURSITEID', environment: 'production'),
));
```

### Plain PHP

```php
use Privatus\Analytics\Config;
use Privatus\Analytics\Tag;

$config = new Config(siteId: 'pa_YOURSITEID', environment: 'production');
echo new Tag($config);
```

The environment comes from `environment`, then the `APP_ENV` environment
variable. If neither is set, the package treats it as `development` and
prints nothing. Set `enabled: true` to print the tag anyway.

### Or let the package add it (Laravel)

If you would rather not touch your layouts, switch on the middleware in
`config/privatus.php`:

```php
'auto_inject' => true,
```

It adds the tag just before `</head>` on every page in the `web` middleware
group. It only changes `text/html` responses with status 200 that are not
compressed. It skips Ajax requests, file downloads, streamed responses, pages
without a `<head>`, and pages that already have the tag. It also fixes the
`Content-Length` header.

You can also add the middleware to some routes only, and leave
`auto_inject` off:

```php
use Privatus\Analytics\Laravel\InjectPrivatusAnalytics;

Route::middleware(InjectPrivatusAnalytics::class)->group(function () {
    // ...
});
```

## Configuration

Every setting is optional except the site id. In Laravel they live in
`config/privatus.php`. Elsewhere, pass them to `new Config(...)` as named
arguments (`siteId`, `excludedPaths` and so on), or to `Config::fromArray()`
with the snake_case names below.

```php
// config/privatus.php
return [
    'site_id'              => env('PRIVATUS_SITE_ID'),
    'script_host'          => env('PRIVATUS_SCRIPT_HOST', 'https://privatusstats.com'),
    'modules'              => ['engage', 'auto', 'vitals', 'clicks', 'ask'],
    'excluded_paths'       => ['/admin/*', '/preview/*'],
    'domains'              => ['example.com', 'www.example.com'],
    'spa'                  => 'auto',   // auto, history, hash or off
    'manual'               => false,    // true turns off the automatic pageview
    'enabled'              => env('PRIVATUS_ENABLED'),
    'enabled_environments' => ['production'],
    'auto_inject'          => false,
    'extra_attributes'     => ['data-mask' => '/orders/*->/orders/:id'],
    'props'                => null,
    'queue_stub'           => false,
];
```

| Setting | Default | What it does |
|---|---|---|
| `site_id` | `PRIVATUS_SITE_ID` | The `data-site` attribute. Nothing prints without it |
| `script_host` | `https://privatusstats.com` | Where `/js/pa.js` is served (or `PRIVATUS_SCRIPT_HOST`). Change it only if you host your own copy |
| `modules` | tracker default (`engage`) | The `data-modules` attribute. Setting it replaces the default, so keep `engage` for time on page and scroll depth. `[]` loads none |
| `excluded_paths` | none | The `data-exclude` attribute. Path patterns the tracker never counts |
| `domains` | all hosts | The `data-domains` attribute. Count only these hostnames |
| `spa` | `auto` | The `data-spa` attribute, for single-page apps |
| `manual` | `false` | The `data-manual` attribute |
| `enabled_environments` | `['production']` | Environments that get the tag |
| `enabled` | `null` | `true` or `false` forces the tag on or off in every environment |
| `environment` | the framework's own | The current environment. Laravel and Symfony fill it in. Plain PHP reads `APP_ENV` |
| `auto_inject` | `false` | Laravel only. Adds the tag with a middleware |
| `extra_attributes` | `[]` | Any other `data-*` attribute |
| `nonce` | the framework's own | Content Security Policy nonce for the script tags. Laravel uses `Vite::cspNonce()` |
| `props` | `null` | Page properties. See [Product analytics](#product-analytics-see-which-features-people-use) |
| `queue_stub` | `false` | Prints a small script so `privatus.track()` works before the tracker loads. It still prints when the tag is off, so those calls never fail |

Each tracker attribute is explained in the [script attributes
reference](https://privatusanalytics.com/docs/tracker/attributes), and the
modules in [Modules](https://privatusanalytics.com/docs/tracker/modules).

You can also change a setting for one page. Every setting works here:

```blade
@privatusAnalytics(['modules' => ['engage', 'auto'], 'excluded_paths' => ['/preview/*']])
<x-privatus-analytics :modules="['engage', 'auto']" />
```

```twig
{{ privatus_analytics({modules: ['engage', 'auto'], excluded_paths: ['/preview/*']}) }}
```

```php
echo new Tag($config, ['modules' => ['engage', 'auto']]);
```

### Development and test

Development, test and staging requests are not counted by default, so your own
clicking around never reaches your reports. To see the tag while you build,
set `PRIVATUS_ENABLED=true` (or `enabled: true`). The tracker also ignores
`localhost` unless you pass `data-allow-local` or `data-debug` (see [Debug
mode](https://privatusanalytics.com/docs/tracker/debug)):

```php
'extra_attributes' => ['data-debug' => 'true'],
```

### Leaving people out

Use `data-exclude` through `excluded_paths` for admin areas. To leave out
signed-in staff, print the tag only for everyone else:

```blade
@unless(auth()->user()?->is_admin)
    @privatusAnalytics
@endunless
```

```twig
{% if not is_granted('ROLE_ADMIN') %}{{ privatus_analytics() }}{% endif %}
```

## Product analytics: see which features people use

These helpers print plain HTML attributes. The tracker in the browser reads
them and sends the events. Your server never sends anything.

### Events on buttons, links and forms

```blade
<button @privatusEvent('Report exported', ['format' => 'csv', 'source' => 'toolbar'])>Export</button>
```

```twig
<button {{ privatus_event('Report exported', {format: 'csv', source: 'toolbar'}) }}>Export</button>
```

```php
use Privatus\Analytics\EventAttributes;

echo '<button ' . EventAttributes::render('Report exported', ['format' => 'csv']) . '>Export</button>';
```

All three print:

```html
<button data-privatus-event="Report exported" data-privatus-prop-format="csv" data-privatus-prop-source="toolbar">Export</button>
```

Put it on a `<form>` and the event fires when the form is sent:

```blade
<form method="post" action="/invite" @privatusEvent('Teammate invited', ['role' => 'editor'])>
```

The rules:

* The name is trimmed and cut to 120 characters. An empty name prints nothing.
* Property names are made lowercase. Anything other than `a-z`, `0-9` and `_`
  becomes `_`. They are cut to 64 characters.
* At most 30 properties. Empty (`null`) values are left out.
* Values become text: `true` and `false`, numbers as written, lists joined
  with commas. Each is cut to 500 characters.
* Everything is HTML-escaped, so it is safe to use with any value.
* The attributes print even when the tag is off, so your development and
  production HTML match.

### Click maps

Give the elements you care about a stable name, so click maps group them
even when the page changes:

```blade
<a href="/pricing" @privatusClick('pricing.cta')>See pricing</a>
```

```twig
<a href="/pricing" {{ privatus_click('pricing.cta') }}>See pricing</a>
```

```php
use Privatus\Analytics\ClickAttribute;

echo '<a href="/pricing" ' . ClickAttribute::render('pricing.cta') . '>See pricing</a>';
```

This prints `data-privatus-click="pricing.cta"`. A name can use letters,
digits and `_ . : -`, and be 1 to 100 characters long. Any other name prints
nothing. In Laravel with `APP_DEBUG=true`, a warning goes to your log too.

### Page properties (plan, role, app version)

Page properties go with every pageview and event on the page. Use them to
split your reports by plan, role, app version, feature flags or customer
tier.

In Laravel, set them for every page from a service provider's `boot` method.
The closure gets the request:

```php
use Illuminate\Http\Request;
use Privatus\Analytics\Laravel\Facades\Privatus;

public function boot(): void
{
    Privatus::props(fn (Request $request) => [
        'plan' => $request->user()?->plan,
        'role' => $request->user()?->role,
        'app_version' => config('app.version'),
    ]);
}
```

If you cache your config, a closure cannot live in `config/privatus.php`.
There, `props` takes an array, or the name of an invokable class that gets the
request and returns an array.

You can also set them for one page:

```blade
<x-privatus-analytics :props="['plan' => 'pro', 'beta' => true]" />
```

```twig
{{ privatus_analytics({props: {plan: 'pro', beta: true}}) }}
```

In Symfony, pass a closure to the extension, for example with the
`!closure` tag (Symfony 6.3 or later) and an invokable service of your own
that reads the request stack:

```yaml
    Privatus\Analytics\Twig\PrivatusExtension:
        arguments:
            $config: '@Privatus\Analytics\Config'
            $props: !closure '@App\Analytics\PageProps'
```

With properties, the tag prints a small script first and points the tracker
at it:

```html
<script>window.privatusPageProps=function(){return {"plan":"pro","role":"admin"}}</script>
<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_YOURSITEID" data-props="privatusPageProps"></script>
```

The names follow the same rules as event properties. Numbers and `true` or
`false` stay as they are, and lists stay lists (at most 30 items). The
characters `<`, `>` and `&` are escaped, so a value can never break out of the
script.

**Never put names, emails, user ids or anything else that points to one
person in page properties.** Privatus Analytics counts visitors without any id. Put
groups (plan, role), not people. The server also removes emails and long ids
it finds.

### Track from your own JavaScript

```js
privatus.track("Feature used", { feature: "export" });
```

If your code can run before the tracker loads, add the queue script. Calls
made early wait in a queue and are sent once the tracker is ready. Set
`queue_stub` to `true`, or for one page:

```blade
@privatusAnalytics(['queue_stub' => true])
```

It prints this before the tag (with your nonce, if you have one):

```html
<script>window.privatus=window.privatus||{q:[]};['track','pageview','props','optOut','optIn','ready'].forEach(function(m){window.privatus[m]=window.privatus[m]||function(){window.privatus.q.push([m,Array.prototype.slice.call(arguments)])}})</script>
```

The queue script prints even where the tag is off (in development, with
`enabled` set to `false`, or with no site id). Then only the queue script
prints: no tracker and no page properties. So you can always call
`privatus.track(...)`. In development the calls just wait in the queue and
are never sent. The middleware adds nothing when the tag is off.

See the [JavaScript API docs](https://privatusanalytics.com/docs/tracker/javascript-api).

### Naming events

Use "Noun verb" names in the past tense, such as `Report exported`,
`Teammate invited` or `Plan upgraded`. Keep the name short and the same
everywhere, and put the details in properties (`format: csv`), not in the
name. Then you can build:

* [Funnels](https://privatusanalytics.com/docs/features/funnels) to see where
  people stop.
* [Click maps](https://privatusanalytics.com/docs/features/click-maps) to see
  what people click.
* [Goals](https://privatusanalytics.com/docs/features/goals) to count the
  actions that matter.
* [Events](https://privatusanalytics.com/docs/events) for everything else.

## Privacy

The script sets no cookies and stores nothing in the browser. This package
sends nothing from your server. See the [privacy
documentation](https://privatusanalytics.com/docs/privacy) for what is
collected.

If you have a privacy policy, it should say which tools handle your visitors' data. You can paste this:

> This website uses [Privatus Analytics](https://privatusanalytics.com/privacy-analytics) to count visits. It
> does not use cookies and does not follow you from one website to another. Your IP address is used for a moment
> to count visits and is never stored.

Change it to fit your site. The [privacy policy generator](https://privatusanalytics.com/tools/privacy-policy-generator)
makes a longer version with your site's name in it.

## Development

The checks run in Docker, so your local PHP does not matter:

```sh
./run-docker.sh
```

It uses the official `php:8.3-cli` image with Composer from the official
`composer` image. It runs the PHPUnit tests (with a small Laravel app from
Orchestra Testbench, and Twig), PHP-CS-Fixer (PSR-12 style), PHPStan (level 8)
and a package build with `composer archive`.

## Contributing

Bug reports and pull requests are welcome at
https://github.com/Privatus-Analytics/php.

## License

MIT. See [LICENSE](LICENSE). Privatus Analytics is a product of Two Phase LLC,
Wyoming, USA.
