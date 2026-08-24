<?php

namespace Aine\Installer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Aine\Installer\Events\InstallerFinished;
use Aine\Installer\Helpers\EnvironmentManager;
use Aine\Installer\Helpers\FinalInstallManager;
use Aine\Installer\Helpers\InstalledFileManager;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param \Aine\Installer\Helpers\InstalledFileManager $fileManager
     * @param \Aine\Installer\Helpers\FinalInstallManager $finalInstall
     * @param \Aine\Installer\Helpers\EnvironmentManager $environment
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function finish(InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment, Request $request)
    {
        // Guard: this step must follow the database step.
        if (! $request->session()->has('installer_summary')) {
            return redirect()->route('LaravelInstaller::welcome');
        }

        $finalMessages = $finalInstall->runFinal();
        $finalStatusMessage = $fileManager->update();

        event(new InstallerFinished);

        return view('vendor.installer.finished', [
            'finalMessages' => $finalMessages,
            'finalStatusMessage' => $finalStatusMessage,
        ]);
    }
}
