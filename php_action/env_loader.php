<?php
/**
 * Simple, robust environment variable loader for PHP without external dependencies.
 * Loads variables from a .env file into $_ENV, $_SERVER, and getenv().
 */

if (!function_exists('loadEnv')) {
    function loadEnv($filePath = null) {
        if ($filePath === null) {
            $filePath = dirname(__DIR__) . '/.env';
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, ';') === 0) {
                continue;
            }

            // Remove leading 'export ' if present
            if (strpos($line, 'export ') === 0) {
                $line = substr($line, 7);
            }

            // Must have an '=' separating key and value
            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            // Handle quoted values
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = substr($value, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                    if ($first === '"') {
                        // Handle escaped characters in double quotes
                        $value = str_replace(['\\n', '\\r', '\\t', '\\"'], ["\n", "\r", "\t", '"'], $value);
                    }
                } else {
                    // Strip inline comment if outside quotes
                    $commentPos = strpos($value, ' #');
                    if ($commentPos !== false) {
                        $value = trim(substr($value, 0, $commentPos));
                    }
                }
            }

            // Handle boolean and null representations
            $lowerVal = strtolower($value);
            if ($lowerVal === 'true') {
                $parsedVal = true;
            } elseif ($lowerVal === 'false') {
                $parsedVal = false;
            } elseif ($lowerVal === 'null') {
                $parsedVal = null;
            } elseif ($lowerVal === 'empty') {
                $parsedVal = '';
            } else {
                $parsedVal = $value;
            }

            // Populate $_ENV, $_SERVER and putenv
            $_ENV[$key] = $parsedVal;
            $_SERVER[$key] = $parsedVal;
            if (is_scalar($parsedVal) || $parsedVal === null) {
                putenv("{$key}={$value}");
            }
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable with a fallback default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null) {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        return $default;
    }
}

// Auto-load .env file upon inclusion
loadEnv();
