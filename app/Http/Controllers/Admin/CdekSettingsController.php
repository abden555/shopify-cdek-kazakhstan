<?php

namespace App\Http\Controllers\Admin;

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
}
