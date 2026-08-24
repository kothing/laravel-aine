<?php

namespace Aine\Installer\Controllers;

use Illuminate\Routing\Controller;

class ConfirmController extends Controller
{
    /**
     * Show a summary of the wizard configuration before the database
     * migration runs, giving the user a last chance to go back.
     *
     * @return \Illuminate\View\View
     */
    public function confirm()
    {
        $summary = session('installer_summary', []);

        if (empty($summary)) {
            $summary = array_filter([
                'app_name' => env('APP_NAME'),
                'environment' => env('APP_ENV'),
                'app_url' => env('APP_URL'),
                'database_connection' => env('DB_CONNECTION'),
                'database_name' => env('DB_DATABASE'),
                'database_host' => env('DB_HOST'),
                'admin_email' => env('ADMIN_EMAIL') ?: session('installer_admin.email'),
            ], fn ($value) => $value !== null && $value !== '');

            // The user did not come through the wizard at all.
            if (empty($summary)) {
                return redirect()->route('LaravelInstaller::environmentWizard');
            }
        }

        return view('vendor.installer.confirm', compact('summary'));
    }
}
