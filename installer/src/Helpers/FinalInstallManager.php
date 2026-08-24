<?php

namespace Aine\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class FinalInstallManager
{
    /**
     * Run final commands.
     *
     * @return string
     */
    public function runFinal()
    {
        $outputLog = new BufferedOutput;

        foreach ([
            $this->generateKey($outputLog),
            $this->publishVendorAssets($outputLog),
            $this->clearCaches($outputLog),
        ] as $result) {
            if (is_array($result) && ($result['status'] ?? null) === 'error') {
                return $result['message'].PHP_EOL.$outputLog->fetch();
            }
        }

        return $outputLog->fetch();
    }

    /**
     * Flush every cached artifact the app may have picked up (config,
     * routes, views, events and the data cache itself).
     *
     * A fresh install must not serve stale cached responses. Even an
     * install "from scratch" can reuse files left on disk by a previous
     * install (e.g. `public_content` entries cached under another host or
     * port), which makes media URLs point at the old address and images
     * 404. Running this right before the installer finishes guarantees the
     * site starts clean on the domain it is actually visited from, with no
     * post-install commands required.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return \Symfony\Component\Console\Output\BufferedOutput|array
     */
    private static function clearCaches(BufferedOutput $outputLog)
    {
        try {
            Artisan::call('optimize:clear', [], $outputLog);
        } catch (Exception $e) {
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Generate New Application Key.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return \Symfony\Component\Console\Output\BufferedOutput|array
     */
    private static function generateKey(BufferedOutput $outputLog)
    {
        try {
            if (config('installer.final.key')) {
                Artisan::call('key:generate', ['--force'=> true], $outputLog);
            }
        } catch (Exception $e) {
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Publish vendor assets.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return \Symfony\Component\Console\Output\BufferedOutput|array
     */
    private static function publishVendorAssets(BufferedOutput $outputLog)
    {
        try {
            if (config('installer.final.publish')) {
                Artisan::call('vendor:publish', ['--all' => true], $outputLog);
            }
        } catch (Exception $e) {
            return static::response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Return a formatted error messages.
     *
     * @param $message
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private static function response($message, BufferedOutput $outputLog)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'dbOutputLog' => $outputLog->fetch(),
        ];
    }
}
