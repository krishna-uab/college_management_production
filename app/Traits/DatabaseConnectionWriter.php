<?php

namespace App\Traits;

// Note: - This trait is used to add a database connection into config/database.php for a particular school

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

trait DatabaseConnectionWriter
{

    public function addConnection(
        string $name,
        string $database,
        string $username,
        string $password,
        string $collegeName
    ): JsonResponse {

        try {

            $path = config_path('database.php');

            if (!is_writable($path)) {
                throw new RuntimeException(
                    'database.php file is not writable.'
                );
            }

            $config = require $path;

            if (isset($config['connections'][$name])) {
                throw new RuntimeException(
                    "Connection '{$name}' already exists."
                );
            }

            $connection = [
                'driver' => 'mysql',
                'url' => null,
                'host' => config('database.connections.default.host'),
                'port' => config('database.connections.default.port'),
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => config(
                    'database.connections.default.options',
                    []
                ),
            ];

            $source = file_get_contents($path);

            if ($source === false) {
                throw new RuntimeException(
                    'Unable to read database.php.'
                );
            }

            $exported = $this->formatArray($connection, 8);

            $pattern = '/([\'"]connections[\'"]\s*=>\s*\[)/';

            if (!preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
                throw new RuntimeException(
                    'Connections array was not found.'
                );
            }

            $start = $matches[0][1] + strlen($matches[0][0]);

            $depth = 1;
            $length = strlen($source);
            $quote = null;
            $escape = false;
            $end = null;

            for ($i = $start; $i < $length; $i++) {
                $char = $source[$i];

                if ($quote !== null) {
                    if ($escape) {
                        $escape = false;
                    } elseif ($char === '\\') {
                        $escape = true;
                    } elseif ($char === $quote) {
                        $quote = null;
                    }

                    continue;
                }

                if ($char === "'" || $char === '"') {
                    $quote = $char;
                } elseif ($char === '[') {
                    $depth++;
                } elseif ($char === ']') {
                    $depth--;

                    if ($depth === 0) {
                        $end = $i;
                        break;
                    }
                }
            }

            if ($end === null) {
                throw new RuntimeException(
                    'Could not locate connections array boundary.'
                );
            }

            // $entry = "\n        "
            //     . var_export($name, true)
            //     . " => "
            //     . $exported
            //     . ",\n    ";

            $comment = '// Group is related to ' . $collegeName;

            $entry = "\n        "
                . $comment
                . "\n        "
                . var_export($name, true)
                . " => "
                . $exported
                . ",\n    ";

            $updated = substr($source, 0, $end)
                . $entry
                . substr($source, $end);

            // Validate generated PHP before writing.
            $temp = tempnam(sys_get_temp_dir(), 'db_config_');

            if ($temp === false) {
                throw new RuntimeException(
                    'Unable to create temporary file.'
                );
            }

            try {
                if (file_put_contents($temp, $updated) === false) {
                    throw new RuntimeException(
                        'Unable to write temporary configuration.'
                    );
                }

                exec(
                    escapeshellarg(PHP_BINARY)
                        . ' -l '
                        . escapeshellarg($temp),
                    $output,
                    $exitCode
                );

                if ($exitCode !== 0) {
                    throw new RuntimeException(
                        'Generated configuration is invalid PHP.'
                    );
                }

                if (file_put_contents($path, $updated, LOCK_EX) === false) {
                    throw new RuntimeException(
                        'Unable to update database.php.'
                    );
                }
            } finally {
                @unlink($temp);
            }

            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'Connection created successfully.',
                'connection' => $connection,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'connection' => null,
            ]);
        }
    }

    private function formatArray(array $data, int $indent = 0): string
    {
        $spaces = str_repeat(' ', $indent);
        $childSpaces = str_repeat(' ', $indent + 2);

        $lines = ['['];

        foreach ($data as $key => $value) {
            $formattedKey = var_export($key, true);

            if (is_array($value)) {
                $formattedValue = $this->formatArray(
                    $value,
                    $indent + 2
                );
            } else {
                $formattedValue = var_export($value, true);
            }

            $lines[] = $childSpaces
                . $formattedKey
                . ' => '
                . str_replace(
                    "\n",
                    "\n" . $childSpaces,
                    $formattedValue
                )
                . ',';
        }

        $lines[] = $spaces . ']';

        return implode("\n", $lines);
    }
}
