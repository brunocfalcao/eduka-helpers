<?php

use Eduka\Cube\Models\Backend;
use Eduka\Cube\Models\Course;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Laravel\Nova\Notifications\NovaNotification;

/**
 * Changes the computed route() domain to another domain passed as
 * parameter. This is useful when we need to change the domain due
 * to course or backend contextualizations.
 *
 * @param  string  $domain
 * @param  mixed  $args
 * @return string
 */
function route_with_custom_domain($domain, ...$args)
{
    // Retrieve the original app.url configuration variable
    $originalAppUrl = Config::get('app.url');
    $parsedOriginalUrl = parse_url($originalAppUrl);

    // Extract the scheme and port from the original app.url
    $originalScheme = $parsedOriginalUrl['scheme'] ?? 'http';
    $originalPort = isset($parsedOriginalUrl['port']) ? ':'.$parsedOriginalUrl['port'] : '';

    // Generate the route URL using the original app.url
    $routeUrl = route(...$args);

    // Parse the generated route URL to get the path and query string
    $parsedUrl = parse_url($routeUrl);
    $path = $parsedUrl['path'] ?? '';
    $query = isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '';

    // Parse the provided domain
    $parsedDomain = parse_url($domain);
    $host = $parsedDomain['host'] ?? $domain;

    // Return the URL with the scheme and port from the original app.url and the host from the provided domain
    return $originalScheme.'://'.rtrim($host, '/').$originalPort.$path.$query;
}

/**
 * Returns the passed view if it exists, or an eduka view on the same
 * namespace path.
 *
 * @param  string $view
 * @return View
 */
function eduka_view_or(string $view)
{
    [$namespace, $view] = explode('::', $view);

    return view()->exists("{$namespace}::{$view}") ?
        "{$namespace}::{$view}" :
        "eduka::{$view}";
}

/**
 * Returns an human date, given the Carbon value passed.
 *
 * @param  mixed $value Carbon value or string
 * @return string
 */
function human_date($value)
{
    $timezone = config('app.timezone');

    if ($value) {
        return (new Carbon($value))->timezone($timezone)
            ->format('F d, Y H:i');
    }
}

/**
 * Returns a human duration (days, hours passed) given the timestamp passed.
 *
 * @param  int $value
 * @return string
 */
function human_duration(int $value)
{
    if (! $value) {
        return '00s'; // Return '00s' if the duration is null or 0
    }

    $hours = floor($value / 3600);
    $minutes = floor(($value / 60) % 60);
    $seconds = $value % 60;

    $parts = [];

    // Include hours only if greater than 0, left-pad with zero if single-digit
    if ($hours > 0) {
        $parts[] = str_pad($hours, 2, '0', STR_PAD_LEFT).'h';
    }

    // Include minutes only if hours are present or minutes are greater than 0, left-pad with zero if single-digit
    if ($hours > 0 || $minutes > 0) {
        $parts[] = str_pad($minutes, 2, '0', STR_PAD_LEFT).'m';
    }

    // Always include seconds, left-pad with zero if single-digit
    $parts[] = str_pad($seconds, 2, '0', STR_PAD_LEFT).'s';

    return implode(' ', $parts);
}

function url_with_app_http_scheme(string $domain)
{
    // Parse the APP_URL to get the scheme and port
    $appUrl = parse_url(config('app.url'));

    // Parse the input domain to get its components
    $domainParts = parse_url($domain);

    // Construct the new URL with the scheme and port from APP_URL
    $scheme = $appUrl['scheme'];
    $host = $domainParts['host'] ?? '';
    $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';
    $path = $domainParts['path'] ?? '';
    $query = isset($domainParts['query']) ? '?'.$domainParts['query'] : '';
    $fragment = isset($domainParts['fragment']) ? '#'.$domainParts['fragment'] : '';

    // Combine the components into the final URL
    return "{$scheme}://{$host}{$port}{$path}{$query}{$fragment}";
}

function push_model_view_namespace(Course|Backend $model)
{
    try {
        // Create a ReflectionClass object for the class
        $reflection = new ReflectionClass($course->provider_namespace);

        // Get the file name where the class is defined
        $filename = $reflection->getFileName();

        // Replace all '\' to '/', get the directory path.
        $path = str_replace('\\', '/', dirname($filename));

        $namespace = $model instanceof Backend ? 'backend' : 'course';

        View::addNamespace($namespace, $path.'/../resources/views');
    } catch (ReflectionException $e) {
        // Handle the error appropriately if the class does not exist
        echo 'Error: '.$e->getMessage();
    }
}

/**
 * Pushes a new filesystem disk for the respective canonical value.
 *
 * @param  string $canonical
 * @return void
 */
function push_canonical_filesystem_disk(string $canonical)
{
    config([
        "filesystems.disks.{$canonical}" => [
            'driver' => 'local',
            'root' => storage_path("app/public/{$canonical}/"),
            'url' => env('APP_URL')."/storage/{$canonical}/",
            'visibility' => 'public',
            'throw' => false,
        ],
    ]);
}

function push_canonicals_filesystem_disks()
{
    $canonicals = array_merge(
        Course::all()->pluck('canonical')->toArray(),
        Backend::all()->pluck('canonical')->toArray()
    );

    foreach ($canonicals as $canonical) {
        config([
            "filesystems.disks.{$canonical}" => [
                'driver' => 'local',
                'root' => storage_path("app/public/{$canonical}/"),
                'url' => env('APP_URL')."/storage/{$canonical}/",
                'visibility' => 'public',
                'throw' => false,
            ],
        ]);
    }
}

function eduka_mail_from(?Course $course)
{
    // Get the admin user for the contextualized course.
    return $course->admin->email;
}

function eduka_mail_name(?Course $course = null)
{
    // Get the admin user name for the contextualized course.
    return $course->admin->name;
}

function eduka_mail_to(?Course $course = null)
{
    // Get the admin user for the contextualized course.
    return $course->admin->email;
}

/**
 * @param  [type] $notifiable The model instance to notify
 * @param  array  $params  ['message', 'action', 'icon', 'type']
 * @return void
 */
function nova_notify($notifiable, array $params)
{
    if (class_exists(NovaNotification::class)) {
        if ($notifiable) {
            $notification = NovaNotification::make();

            foreach (['message', 'action', 'icon', 'type'] as $key) {
                if (array_key_exists($key, $params)) {
                    $notification->$key($params[$key]);
                }
            }

            $notifiable->notify($notification);
        } else {
            info('-- '.$params['message'].' --');
        }
    }
}
