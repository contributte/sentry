![](https://heatbadger.now.sh/github/readme/contributte/sentry/)

<p align=center>
  <a href="https://github.com/contributte/sentry/actions"><img src="https://badgen.net/github/checks/contributte/sentry/master?cache=300"></a>
  <a href="https://coveralls.io/r/contributte/sentry"><img src="https://badgen.net/coveralls/c/github/contributte/sentry?cache=300"></a>
  <a href="https://packagist.org/packages/contributte/sentry"><img src="https://badgen.net/packagist/dm/contributte/sentry"></a>
  <a href="https://packagist.org/packages/contributte/sentry"><img src="https://badgen.net/packagist/v/contributte/sentry"></a>
</p>
<p align=center>
  <a href="https://packagist.org/packages/contributte/sentry"><img src="https://badgen.net/packagist/php/contributte/sentry"></a>
  <a href="https://github.com/contributte/sentry"><img src="https://badgen.net/github/license/contributte/sentry"></a>
  <a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
  <a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
  <a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

# Contributte Sentry

Sentry integration for Nette applications with Tracy logging, Nette context integrations, and performance monitoring helpers.

## Versions

| State       | Version | Branch   | Nette | PHP     |
|-------------|---------|----------|-------|---------|
| dev         | `^0.5`  | `master` | 3.2+  | `>=8.2` |
| stable      | `^0.4`  | `master` | 3.2+  | `>=8.2` |

## Contents

- [Setup](#setup)
- [Configuration](#configuration)
- [Extra](#extra)
- [Usage](#usage)
- [Examples](#examples)

## Setup

Install composer package.

```bash
composer require contributte/sentry
```

Register Nette extension.

```
extensions:
    sentry: Contributte\Sentry\DI\SentryExtension
```

## Configuration

Minimal configuration could look like this:

```neon
sentry:
    # Enabled only on production
    enable: %productionMode%

    # Client configuration
    client:
        dsn: "https://{KEY1}@{KEY2}.ingest.sentry.io/{KEY3}"
```

Full configuration could look like this:

```neon
sentry:
    # Enable / disable on local or producation
    enable: %productionMode%

    # Enable / disable build-in and Nette integrations
    integrations: true

    # Sentry logger configuration
    logger:
        # Capture messages [Debugger::log("this is error")]
        captureMessages: true

        # Capture levels [Debugger::log("this is error", "error")]
        captureLevels:
            # - debug
            # - info
            - warning
            - error
            - exception
            - critical

    # Sentry client configuration
    client:

        # Disable integrations in sentry.integrations if you want to override this list
        integrations: [
            # Sentry
            Sentry\Integration\EnvironmentIntegration()
            Sentry\Integration\ErrorListenerIntegration()
            Sentry\Integration\ExceptionListenerIntegration()
            Sentry\Integration\FatalErrorListenerIntegration()
            Sentry\Integration\FrameContextifierIntegration(null)
            Sentry\Integration\ModulesIntegration()
            Sentry\Integration\RequestIntegration(null)
            Sentry\Integration\TransactionIntegration()
            # Nette
            Contributte\Sentry\Integration\NetteApplicationIntegration()
            Contributte\Sentry\Integration\NetteSecurityIntegration()
            Contributte\Sentry\Integration\NetteSessionIntegration()
            Contributte\Sentry\Integration\ExtraIntegration([
                # version: %appVersion%
            ])
        ]
        default_integrations: false
        send_attempts: 3
        prefixes: [%appDir%]
        sample_rate: 1
        attach_stacktrace: true
        context_lines: 6
        enable_compression: true
        environment: local
        logger: php
        release: latest
        dsn: "https://{KEY1}@{KEY2}.ingest.sentry.io/{KEY3}"
        server_name: ::gethostname()
        # before_send: ()
        tags:
            # version: %appVersion%
        error_types: 32767 # E_ALL
        max_breadcrumbs: 100
        # before_breadcrumb: ()
        in_app_exclude: []
        send_default_pii: true
        max_value_length: 1024
        http_proxy: null
        capture_silenced_errors: false
        max_request_body_size: always
        class_serializers: []
        traces_sample_rate: 1

    # The only way to customize client builder:
    clientBuilder:
        representationSerializer: @representationSerializer
        logger: @representationSerializer
        sdkIdentifier: foo
        sdkVersion: 1.0
        transport: @transport
```

See more about configuration under key `sentry` in [official documentation](https://docs.sentry.io/platforms/php/).

## Extra

### Integrations

> Read about integrations in [official Sentry documentation](https://docs.sentry.io/platforms/php/integrations/).

Integrations are some kind of plugins for Sentry SDK. This package brings several integrations.

**NetteApplicationIntegration**

Add information about `nette/application` to Sentry event.

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\NetteApplicationIntegration()
```

**NetteHttpIntegration**

Add information about `nette/http` to Sentry event. It's preferred to use default integration (https://github.com/getsentry/sentry-php/blob/master/src/Integration/RequestIntegration.php).

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\NetteHttpIntegration()
```

**NetteSecurityIntegration**

Add information about `nette/security` to Sentry event.

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\NetteSecurityIntegration()
```

**NetteSessionIntegration**

Add information about `nette/session` to Sentry event.

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\NetteSessionIntegration()
```

**ExtraIntegration**

Add extra data to Sentry event.

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\ExtraIntegration([
                version: %appVersion%
            ])
```

**IgnoreErrorIntegration**

Allow to ignore exceptions or event message.

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\IgnoreErrorIntegration([
                ignore_exception_instance: [
                    FooException::class,
                    BarException::class,
                ],
                ignore_exception_regex: [
                    '/Deprecated (.*)/'
                ],
                ignore_message_regex: [
                    '/PHP Deprecated (.*)/'
                ],
            ])
```

Be careful with `"` and `'`. It does matter.

**S3UploadIntegration**

Upload **ladenka** (error file) to S3. URL is stored as tag in Sentry.

```neon
sentry:
    client:
        integrations:
            - Contributte\Sentry\Integration\S3UploadIntegration()

services:
    - Contributte\Sentry\Upload\S3Uploader(
        Contributte\Sentry\Upload\S3Signer(
          accessKeyId: secret
          secretKey: secret
          url: myorg.r2.cloudflarestorage.com / s3.eu-central-1.amazonaws.com
          bucket: mybucket
          region: auto
          prefix: null
        )
    )
```

### Performance

> Read about performance tracking in [official Sentry documentation](https://docs.sentry.io/platforms/php/performance/).

Performance monitors help you measure application spans.

**NetteApplicationMonitor**

Measure Nette Application lifecycle from request to shutdown.

```neon
services:
	- Contributte\Sentry\Performance\NetteApplicationMonitor

	application.application:
		setup:
			- @Contributte\Sentry\Performance\NetteApplicationMonitor::hook(@self, 'onRequest')
			- @Contributte\Sentry\Performance\NetteApplicationMonitor::hook(@self, 'onShutdown')
			# or
			- "$onRequest[]" = [@Contributte\Sentry\Performance\NetteApplicationMonitor, onRequest]
			- "$onShutdown[]" = [@Contributte\Sentry\Performance\NetteApplicationMonitor, onShutdown]
```

Don't forget to specify `traces_sample_rate` parameter, which means uniform sample rate for all transactions to a number between 0 and 1.
For example, to send 20% of transactions, set `traces_sample_rate` to `0.2`.

```neon
sentry:
        client:
                # Trace every transaction, recommended for testing/dev
                traces_sample_rate: 1
```

## Usage

Sentry is successfully integrated to your [Nette](https://nette.org) application and listen for any errors using [Tracy](https://tracy.nette.org).

Let's test it using this smelling code.

```php
<?php declare(strict_types=1);

namespace App;

use Nette\Application\UI\Presenter;

class HomePresenter extends Presenter
{

    public function actionDefault(): void
    {
        throw new \RuntimeException('This is example error');
    }

}
```

## Examples

There is example project [contributte/sentry-skeleton](https://github.com/contributte/sentry-skeleton).

## Development

See [how to contribute](https://contributte.org) to this package. This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
    <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider to [support](https://contributte.org/partners) **contributte** development team.
Also thank you for using this package.
