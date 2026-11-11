<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Proyecto: Repositorio de Proyectos UNAB

- **Frontend:** vive en este repo, en `resources/js` (React 19 + TanStack Router/Query + Tailwind v4). Es una SPA servida por Laravel (`resources/views/app.blade.php`, ruta comodín en `routes/web.php`) que consume `/api/v1` con token Bearer de Sanctum, desde el mismo origen (sin CORS). Vino de Lovable, pero **los cambios se hacen aquí, no en Lovable**.
- **Contrato:** manda el backend. Los tipos del frontend (`resources/js/lib/api/tipos.ts`) se copian de los API Resources; si cambias un Resource, actualiza los tipos.
- **Convenciones:** commits pequeños en español (Conventional Commits), `documentacion.md` actualizado en cada paso, nunca subir `.env`.
- **Verificar:** `./vendor/bin/pest --testsuite=Feature`, `npm test`, `npm run typecheck`, `npm run build`.
