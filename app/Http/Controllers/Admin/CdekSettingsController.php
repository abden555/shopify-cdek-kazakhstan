<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Carriers\DTOs\CarrierCredentialsData;
use App\Domains\Carriers\Exceptions\CarrierRequestException;
use App\Domains\Carriers\Services\CdekCarrier;
use App\Domains\Carriers\Services\CdekSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCdekSettingsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CdekSettingsController extends Controller
{
    public function edit(CdekSettingsService $settings): View
    {
        return view('admin.settings.cdek', ['settings' => $settings->formValues()]);
    }

    public function update(UpdateCdekSettingsRequest $request, CdekSettingsService $settings): RedirectResponse
    {
        $settings->save($request->validated());

        return to_route('admin.settings.cdek.edit')->with('status', 'CDEK settings saved securely.');
    }

    public function test(CdekSettingsService $settings, CdekCarrier $carrier): RedirectResponse
    {
        $configuration = $settings->configuration();

        if (blank($configuration->clientId) || blank($configuration->clientSecret)) {
            return to_route('admin.settings.cdek.edit')->with('error', 'Save a CDEK Client ID and Client secret before testing the connection.');
        }

        try {
            $authentication = $carrier->authenticate(new CarrierCredentialsData(
                clientId: $configuration->clientId,
                clientSecret: $configuration->clientSecret,
            ));

            return to_route('admin.settings.cdek.edit')->with(
                'status',
                'CDEK connection succeeded. Credentials are valid'.($authentication->expiresAt ? ' until '.$authentication->expiresAt->format('Y-m-d H:i:s T').'.' : '.'),
            );
        } catch (CarrierRequestException) {
            return to_route('admin.settings.cdek.edit')->with('error', 'CDEK connection failed. Check the environment and credentials; technical details are recorded in Failed API Logs.');
        }
    }
}
