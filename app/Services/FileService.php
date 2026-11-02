<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class FileService
{

    public static function upload($file, $path, $prefix = null)
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $directory = public_path($path);

        File::ensureDirectoryExists($directory);

        $extension = strtolower($file->getClientOriginalExtension());

        $fileName = ($prefix ? $prefix . '_' : '')
            . Str::uuid()
            . '.' . $extension;

        try {
            $file->move($directory, $fileName);

            return $fileName;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function update($file, $path, $prefix,  $oldFile = null)
    {
        if (!$file) {
            return $oldFile;
        }
        $newFile = self::upload($file, $path, $prefix);

        if (!$newFile) {
            return $oldFile;
        }

        if ($oldFile) {
            self::delete($oldFile);
        }

        return $newFile;
    }

    public static function delete($filePath)
    {
        if (!$filePath) {
            return false;
        }

        $basePath = realpath(public_path());

        if (!$basePath) {
            return false;
        }

        $fullPath = public_path($filePath);
        $realFilePath = realpath($fullPath);
        if (
            !$realFilePath ||
            !str_starts_with(
                $realFilePath,
                $basePath . DIRECTORY_SEPARATOR
            ) ||
            !is_file($realFilePath)
        ) {
            return false;
        }

        return File::delete($realFilePath);
    }
}
