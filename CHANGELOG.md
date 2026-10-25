# Changelog

## Unreleased

- Changed: with `queue_stub` on, a tag that is off (outside production, `enabled` set to `false`, or no site id)
  now prints the queue script and nothing else, so `privatus.track(...)` calls in your own JavaScript never fail.
  Before, it printed nothing. With `queue_stub` off it still prints nothing. The middleware still adds nothing
  when the tag is off.
- Added `example/`, a small Laravel app for the conformance tests.

## 1.0.0 - 2026-10-25

- Initial release.
- `Tag` builds the script tag in plain PHP. It prints nothing without a site id or outside production.
- `EventAttributes::render()` for `data-privatus-event` attributes on buttons, links and forms.
- `ClickAttribute::render()` for `data-privatus-click` names used by click maps.
- Page properties (`props`) sent with every pageview and event, and an optional queue script for `privatus.track()`.
- Laravel: auto-discovered service provider, `config/privatus.php`, `@privatusAnalytics`, `@privatusEvent`,
  `@privatusClick`, `<x-privatus-analytics />`, the `Privatus` facade, the Vite nonce, and an optional middleware
  that adds the tag to the head of HTML pages (off by default).
- Twig: `privatus_analytics()`, `privatus_event()` and `privatus_click()` functions for Symfony and other Twig apps.
