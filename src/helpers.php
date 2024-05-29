<?php

use Eduka\Cube\Models\Course;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Laravel\Nova\Notifications\NovaNotification;

if (! function_exists('eduka_route')) {
    /**
     * Generate a URL for the given named route with a specified domain.
     *
     * @param  string  $domain  The domain to use in the generated URL.
     * @param  mixed  ...$args  The parameters for the route function.
     * @return string
     */
    function eduka_route(...$args)
    {
        // Retrieve the domain from the app.url configuration variable
        $appUrl = Config::get('app.url');
        $parsedAppUrl = parse_url($appUrl);
        $scheme = $parsedAppUrl['scheme'].'://';
        $domain = $parsedAppUrl['host'];

        // Include the port if specified in the app.url
        if (isset($parsedAppUrl['port'])) {
            $domain .= ':'.$parsedAppUrl['port'];
        }

        $routeUrl = route(...$args);

        // Parse the generated route URL to get the path and query string
        $parsedUrl = parse_url($routeUrl);
        $path = $parsedUrl['path'] ?? '';
        $query = isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '';

        // Return the URL with the domain from the configuration
        return $scheme.rtrim($domain, '/').$path.$query;
    }
}

if (! function_exists('override_app_url')) {
    function override_app_url($domain)
    {
        $scheme = parse_url(request()->fullUrl())['scheme'];

        config(['app.url' => $scheme.
                             '://'.
                             $domain]);
    }
}

if (! function_exists('course_or_eduka_view')) {
    function course_or_eduka_view($view)
    {
        return view()->exists("course::{$view}") ?
            "course::{$view}" :
            "eduka::{$view}";
    }
}

if (! function_exists('course_or_backend_view')) {
    function course_or_backend_view($view)
    {
        return view()->exists("backend::{$view}") ?
            "backend::{$view}" :
            "eduka::{$view}";
    }
}

if (! function_exists('human_date')) {
    function human_date($value)
    {
        $timezone = config('app.timezone');

        if ($value) {
            return (new Carbon($value))->timezone($timezone)
                ->format('F d, Y H:i');
        }
    }
}

if (! function_exists('human_duration')) {
    function human_duration($value)
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
}

if (! function_exists('extract_host_from_url')) {
    /**
     * Extracts host name from a URL.
     *
     * @param  string  $url
     * @return string|null
     */
    function extract_host_from_url($url)
    {
        $parsedUrl = parse_url($url);

        // Check if the host name exists in the parsed URL
        if (isset($parsedUrl['host'])) {
            $host = $parsedUrl['host'];

            // Remove www. prefix if exists
            $host = Illuminate\Support\Str::startsWith($host, 'www.') ? substr($host, 4) : $host;

            // Remove port number if exists
            $host = strtok($host, ':');

            return $host;
        }

        // Return null if the host name couldn't be extracted
        return null;
    }
}

/**
 * Mostly used to render full urls on newsletters and jobs that
 * will render views that have a course contextualized somehow.
 */
function eduka_url(?string $pathSuffix = null): string
{
    $slash = str_starts_with(Storage::url($pathSuffix), '/') ||
             str_ends_with(env('APP_URL'), '/') ?
             '' : '/';

    return env('APP_URL').$slash.Storage::url($pathSuffix);
}

function register_course_view_namespace(Course $course)
{
    try {
        // Create a ReflectionClass object for the class
        $reflection = new ReflectionClass($course->provider_namespace);

        // Get the file name where the class is defined
        $filename = $reflection->getFileName();

        // Replace all '\' to '/', get the directory path.
        $path = str_replace('\\', '/', dirname($filename));

        View::addNamespace('course', $path.'/../resources/views');
    } catch (ReflectionException $e) {
        // Handle the error appropriately if the class does not exist
        echo 'Error: '.$e->getMessage();
    }
}

function push_course_filesystem_driver(Course $course)
{
    config([
        'filesystems.disks.course' => [
            'driver' => 'local',
            'root' => storage_path('app/public/'.$course->canonical.'/'),
            'url' => env('APP_URL').'/storage/'.$course->canonical.'/',
            'visibility' => 'public',
            'throw' => false,
        ],
    ]);
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

if (! function_exists('nova_notify')) {

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
}
