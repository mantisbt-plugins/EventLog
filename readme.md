EventLog is a logging plugin for Mantis Bug Tracker.  This allows MantisBT components to log data that is then viewed via the EventLog plugin.

# Installation Instructions

- Place the EventLog folder under the MantisBT plugin folder.

- Go to Manage - Manage Plugins and install the plugin.

- Setup logging level in config_inc.php (see config_defaults_inc.php for documentation).  For example:

```php
$g_log_level = LOG_EMAIL | LOG_EMAIL_RECIPIENT
```

- Do some MantisBT activity (e.g. add notes, report bugs, etc).

- Go to the event log view by clicking: Manage - Event log

# REST API

The plugin exposes its event log over the MantisBT REST API so that clients can
read and clear it directly:

- `GET /api/rest/plugins/EventLog?page=1&per_page=10` — returns a
  paginated list of requests, each with its user reference and associated
  events. Timestamps are returned in ISO-8601 format. Only sanitized
  `event_html` is returned per event (display links and `@U`/`@P` user/project
  references already resolved); the raw event text is not exposed. `per_page` is
  clamped to `1..100`. Requires `view_threshold`.
- `DELETE /api/rest/plugins/EventLog` — clears the entire event log
  (all requests and events). Returns `204 No Content`. Requires
  `manage_threshold`.

# Demo

You can see a demo of it on [MantisHub](http://www.mantishub.com) where it is used to help administrators
understand email notifications, answering questions like why did user X receive or didn't receive an email
notification for issue Y.

# Screenshot

![EventLog Screentshot](wiki/eventlog_screenshot.png "EventLog Screentshot")

# Compatibility

- Supports MantisBT v2.x -- use master branch
- Supports MantisBT v1.3.x -- use master-1.3.x branch
- Supports MantisBT v1.2.x -- use master-1.2.x branch

