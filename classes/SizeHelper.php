<?php namespace Chocolata\ChocoClear\Classes;

class SizeHelper
{
    /**
     * Berekent de totale grootte (in bytes) van een map.
     *
     * @param string      $path            Basismap
     * @param bool        $followSymlinks  Volg symlinks?
     * @param string|null $pattern         Regex om te filteren
     * @param bool        $matchOnPath     true = match op volledig pad, false = enkel bestandsnaam
     **/

    public static function dirSize(
        string $path,
        bool $followSymlinks = false,
        ?string $pattern = null,
        bool $matchOnPath = false
    ): int {
        if (!is_dir($path)) {
            return 0;
        }

        // Optionele regex-validatie (gooi duidelijke fout bij ongeldige regex)
        if ($pattern !== null && @preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException("Invalid regex pattern '{$pattern}'.");
        }

        $flags = \FilesystemIterator::SKIP_DOTS
               | \FilesystemIterator::CURRENT_AS_FILEINFO
               | \FilesystemIterator::KEY_AS_PATHNAME;

        if ($followSymlinks) {
            $flags |= \RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
        }

        $size = 0;
        $dir  = new \RecursiveDirectoryIterator($path, $flags);
        $it   = new \RecursiveIteratorIterator($dir);

        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            try {
                if (!$file->isFile()) {
                    continue;
                }

                if ($pattern !== null) {
                    $subject = $matchOnPath ? $file->getPathname() : $file->getFilename();
                    if (preg_match($pattern, $subject) !== 1) {
                        continue;
                    } else {
                        //\Log::info("Matched file: {$file->getPathname()} with pattern: {$pattern} and size {$file->getSize()} bytes");
                    }
                }

                $size += $file->getSize();
            } catch (\Throwable $e) {
                // Onleesbaar of race condition -> overslaan
                continue;
            }
        }

        return $size;
    }

    public static function formatSize(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B','KB','MB','GB','TB','PB','EB','ZB','YB'];
        $pow = (int) floor(log($bytes, 1024));
        $pow = max(0, min($pow, count($units) - 1));
        $value = $bytes / (1024 ** $pow);
        return number_format($value, $precision) . ' ' . $units[$pow];
    }
}
