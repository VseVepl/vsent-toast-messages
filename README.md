# Laravel Toastify

[![Latest Stable Version](https://img.shields.io/packagist/v/vsent/laravel-toastify.svg?style=flat-square)](https://packagist.org/packages/vsent/laravel-toastify)
[![Total Downloads](https://img.shields.io/packagist/dt/vsent/laravel-toastify.svg?style=flat-square)](https://packagist.org/packages/vsent/laravel-toastify)
[![License](https://img.shields.io/packagist/l/vsent/laravel-toastify.svg?style=flat-square)](LICENSE.md)
<!-- [![Build Status](https://img.shields.io/travis/com/vsent/laravel-toastify/main.svg?style=flat-square)](https://travis-ci.com/vsent/laravel-toastify) -->

Laravel Toastify provides a highly configurable, Livewire-powered system for displaying dynamic toast notifications in your Laravel application. It leverages Tailwind CSS for styling and Alpine.js for smooth frontend interactivity.

This package offers a rich set of features including various animation styles, sound effects, priority-based queuing, progress bars, interactive actions within toasts, and extensive customization options through a detailed configuration file.

## Features

*   **Multiple Toast Types:** Predefined types (`success`, `error`, `warning`, `info`, `default`, `critical`) and the ability to define fully custom types.
*   **Rich Styling & Appearance:** Control background colors, text colors, icons, and overall layout using Tailwind CSS classes.
*   **Livewire Powered:** Seamless real-time display and updates via a `ToastContainer` component.
*   **Animations:** Customizable enter/leave animations for toasts (slide, fade, scale, etc.).
*   **Sound Effects:** Play sounds when toasts appear or are dismissed, configurable per type.
*   **Progress Bars:** Optional visual indicator for auto-dismissing toasts.
*   **Interactive Actions:** Add buttons to toasts that can trigger Livewire actions or JavaScript functions.
*   **Queuing & Priority System:** Manage how many toasts display, their order, and priority levels.
*   **Configurable Behavior:** Control auto-dismissal, pause on hover/blur, duplicate detection, and more.
*   **Accessibility:** Built with ARIA attributes for live regions, roles, and labels.
*   **Easy Integration:** Simple API via Facade (`Toastify`), global helper functions (`toastify_success`), and a `WithToastMessages` trait for Livewire components.
*   **Console Commands:** For easy installation and uninstallation of package resources.
*   **Events:** Dispatches a `ToastCreated` event for custom integrations.

## Requirements

To use Laravel Toastify, your project should meet the following requirements:

*   **PHP:** `^8.2`
*   **Laravel Framework:** `^11.0` or `^12.0`
*   **Livewire:** `^3.0`
*   **Tailwind CSS:** v3 or v4 (Package developed assuming v4 compatibility for configured classes)
*   **Alpine.js:** Required for frontend interactivity (assumed to be part of your project setup)

## Installation

Follow these steps to install and set up Laravel Toastify in your Laravel project:

### 1. Require Package via Composer

Open your terminal and navigate to your Laravel project's root directory. Then, run the following Composer command:

```bash
composer require vsent/laravel-toastify
```

### 2. Service Provider & Facade (Auto-Discovery)

For Laravel versions that support package auto-discovery (typically Laravel 5.5+), the `ToastServiceProvider` and the `Toastify` facade should be automatically registered.

If you have disabled package auto-discovery, or for older Laravel versions, you'll need to register them manually:

*   **Service Provider:** Add the following line to the `providers` array in your `config/app.php` file:
    ```php
    'providers' => [
        // Other Service Providers
        Vsent\LaravelToastify\ToastServiceProvider::class,
    ],
    ```

*   **Facade:** Add the following line to the `aliases` array in your `config/app.php` file:
    ```php
    'aliases' => Facade::defaultAliases()->merge([
        // Other Facades
        'Toastify' => Vsent\LaravelToastify\Facades\Toastify::class,
    ])->toArray(),
    ```

### 3. Publish Resources

To use Laravel Toastify, you need to publish its configuration file, views (optional, if you want to customize them), and sound assets. Run the following Artisan command:

```bash
php artisan toastify:install
```

This command will:
*   Publish the `toasts.php` configuration file to your `config/` directory.
*   Publish the Blade views to `resources/views/vendor/laravel-toastify/`.
*   Publish sound assets to `public/vendor/vsent/laravel-toastify/sounds/`.

You can also publish resources selectively:
*   **Configuration only:**
    ```bash
    php artisan toastify:install --config-only
    ```
*   **Views only:**
    ```bash
    php artisan toastify:install --views-only
    ```
*   **Assets (sounds) only:**
    ```bash
    php artisan toastify:install --assets-only
    ```
Use the `--force` option to overwrite existing published files.

### 4. Include Livewire Assets & Toast Container

Ensure your main Blade layout file(s) (e.g., `resources/views/layouts/app.blade.php`) include Livewire's required assets and the Toastify Livewire component. Typically, this means:

*   `@livewireStyles` in the `<head>` section.
*   `@livewireScripts` before the closing `</body>` tag.
*   The Toastify container component, usually placed just before `@livewireScripts` or the closing `</body>` tag:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel</title>
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js']) {{-- Or your asset bundling --}}
    @livewireStyles
</head>
<body>
    {{-- Your application content --}}
    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <livewire:toastify-container /> {{-- Add this line --}}

    @livewireScripts
</body>
</html>
```
Ensure your `tailwind.config.js` is configured to scan `resources/views/vendor/laravel-toastify/**/*.blade.php` if you publish and customize views, to ensure Tailwind CSS classes are processed.

---

## Configuration

The primary configuration for Laravel Toastify is done through the `config/toasts.php` file. After publishing it using `php artisan toastify:install --config-only` (or the general install command), you can modify it extensively.

Key sections within `config/toasts.php`:

*   **`session_key`**: Session key for storing toasts.
*   **`animations`**: Define animation presets (`slide_from_bottom`, `fade`, etc.) and global animation defaults (durations, easings, Tailwind classes).
*   **`behavior`**: Control global toast behaviors like `auto_dismiss`, `pause_on_hover`, `pause_on_window_blur`, `reverse_order_on_stack`, `clear_all_on_navigate`, and `duplicate_detection`.
*   **`close_button`**: Customize the appearance and behavior of the close button (icon, classes, ARIA label).
*   **`display`**: Set default toast duration, screen position (desktop and mobile), and max-width.
*   **`progress_bar`**: Configure the progress bar's appearance (height, colors, transitions) and enable/disable it globally or per type.
*   **`queue`**: Manage toast queuing with `max_toasts` (overall limit), `per_type_limit`, `lifetime` (in session), and a `priority` system (levels, limits per level, overflow behavior).
*   **`sounds`**: Globally enable/disable sounds, define a `base_path` for sound files, set default sounds, and manage a library of `assets` (sound files with volume, loop settings). Map toast `types` to these sound assets.
*   **`types`**: This is the core section for defining individual toast types (e.g., `success`, `error`, `custom_promo`).
    *   `defaults`: Base properties for all types.
    *   `layouts`: Define structural layouts for toasts using Tailwind classes.
    *   Each specific type can override defaults for `duration`, `bg` (background), `text_color`, `icon` (SVG), `sound`, `priority`, `animation_preset`, `layout_preset`, `progress_bar` settings, and define `actions` (buttons with labels, handlers, classes).

Please refer to the heavily commented `config/toasts.php` file itself for detailed explanations of each option.

---

## Usage

Laravel Toastify offers multiple ways to dispatch notifications:

### Using the `Toastify` Facade

```php
use Vsent\LaravelToastify\Facades\Toastify;

// Simple success toast
Toastify::success('Profile updated successfully!');

// Error toast with a title
Toastify::error('Failed to upload file.', 'Upload Error');

// Warning toast with a specific duration (ms)
Toastify::warning('Subscription expiring soon.', 'Alert', 7000);

// Custom toast
Toastify::custom(
    message: 'This is a highly customized toast.',
    title: 'Special Info',
    options: [
        'type' => 'special_promo', // Defined in config/toasts.php
        'duration' => 10000,
        'priority' => 'high',
        'soundAsset' => 'promo_sound', // Key from sounds.assets in config
        'actions' => [
            ['label' => 'Details', 'handler' => "Livewire.dispatch('show-promo-details')"],
        ]
    ]
);
```

### Using Global Helper Functions

```php
// Simple success toast
toastify_success('Comment posted.');

// Error toast with a title
toastify_error('Invalid input.', 'Validation Failed');

// Generic helper for any type
toastify('info', 'System update complete.', 'System Info');

// Custom toast
toastify_custom('Welcome back!', 'Greetings', ['type' => 'welcome_toast']);
```

### Using the `WithToastMessages` Trait

In your Livewire components or other classes:

```php
namespace App\Http\Livewire;

use Livewire\Component;
use Vsent\LaravelToastify\Traits\WithToastMessages;

class UserSettings extends Component
{
    use WithToastMessages;

    public function updateSettings()
    {
        // ... logic ...
        $this->addSuccessToast('Settings saved!');
        // $this->addErrorToast('Save failed.', 'Error');
    }
    // ...
}
```

### Displaying Toasts
Toasts are automatically displayed by the `<livewire:toastify-container />` component in your layout.

---
## Console Commands

### `php artisan toastify:install`
Publishes package resources (config, views, assets).
*   `--force`: Overwrite existing files.
*   `--config-only`: Publish only `config/toasts.php`.
*   `--views-only`: Publish only Blade views.
*   `--assets-only`: Publish only sound assets.

### `php artisan toastify:uninstall`
Removes published resources after confirmation. Does not remove the Composer package or `config/app.php` entries.

---
## Events

### `Vsent\LaravelToastify\Events\ToastCreated`
Dispatched when a new toast is created. Contains a public readonly `$toast` property (instance of `ToastMessageDTO`).
You can create listeners for this event to perform actions like logging:

```php
// app/Listeners/LogToastNotification.php
namespace App\Listeners;

use Vsent\LaravelToastify\Events\ToastCreated;
use Illuminate\Support\Facades\Log;

class LogToastNotification
{
    public function handle(ToastCreated $event): void
    {
        Log::info(sprintf('Toast Created: ID=%s, Type=%s', $event->toast->id, $event->toast->type));
    }
}
```
Register your listener in `EventServiceProvider.php`.

---
## Testing (Package Development)

This package uses Pest for testing. To run tests for package development:
1. Clone the package repository.
2. Install dev dependencies: `composer install`.
3. Run tests: `./vendor/bin/pest`.

---
## Contributing

Contributions are welcome! Please refer to `CONTRIBUTING.md` (if available) or follow standard practices: fork, branch, test, document, and submit a pull request.

---
## License

Laravel Toastify is open-sourced software licensed under the [MIT license](LICENSE.md).
```
