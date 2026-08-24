<?php

namespace Aine\Installer\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Aine\Installer\Helpers\DatabaseManager;
use Spatie\Permission\Models\Role;

class DatabaseController extends Controller
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * Migrate and seed the database, then create the admin account
     * passed through the session by the wizard.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function database(Request $request)
    {
        // Guard: this step must follow the wizard/confirmation flow.
        if (! $request->session()->has('installer_summary')) {
            return redirect()->route('LaravelInstaller::environmentWizard');
        }

        $response = $this->databaseManager->migrateAndSeed();

        // If migration/seeding failed, do NOT proceed to create the admin
        // account or show the "installed" success page. Send the user back
        // to the environment wizard with the error message and old input.
        if (($response['status'] ?? null) === 'error') {
            return redirect()->route('LaravelInstaller::environment')
                ->with('message', $response)
                ->withInput();
        }

        // Admin credentials were stored in the session by the wizard (never
        // written to disk in plaintext).
        $admin = $request->session()->pull('installer_admin');

        if (empty($admin['email'])) {
            // Session data lost (e.g. the cookie expired between the confirm
            // page and this step): never install without an admin account —
            // send the user back to the wizard to re-submit.
            return redirect()->route('LaravelInstaller::environmentWizard')
                ->withErrors(['admin_email' => trans('installer_messages.confirm.admin_email_missing')]);
        }

        // This project has no "hashed" cast on the User model, so the
        // password must be hashed explicitly. firstOrCreate keeps the
        // step idempotent: going back and re-running the database step
        // (or re-submitting the wizard) never crashes on a duplicate.
        $user = User::firstOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'] ?? '',
                'password' => Hash::make($admin['password']),
            ]
        );
        // The account may already exist (e.g. created by the demo seeder
        // with the default password) — always apply the wizard password and
        // name so the credentials entered by the installer actually work.
        $user->name = $admin['name'] ?? $user->name;
        $user->password = Hash::make($admin['password']);
        // email_verified_at is not mass-assignable; set it directly.
        $user->email_verified_at = now();
        $user->save();

        // The admin needs the super_admin role to access the backend.
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        if (! $user->hasRole('super_admin')) {
            $user->assignRole($role);
        }

        // Minimal default settings record (id=1) required by the app.
        $setting = Setting::first();
        if (! $setting) {
            Setting::create([
                'name' => config('app.name', 'My Website'),
                'description' => 'My Website Description',
            ]);
        }

        return redirect()->route('LaravelInstaller::final')
                         ->with(['message' => $response]);
    }
}
