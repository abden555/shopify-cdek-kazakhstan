<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

final class BrandingController extends Controller
{
    private const SETTING_KEY = 'application_branding';

    public function edit(): View
    {
        return view('admin.settings.branding', ['branding' => $this->branding()]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $branding = $this->branding();

        if ($request->boolean('remove_logo') && filled($branding['logo_path'] ?? null)) {
            Storage::disk('public')->delete($branding['logo_path']);
            $branding['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if (filled($branding['logo_path'] ?? null)) {
                Storage::disk('public')->delete($branding['logo_path']);
            }

            $branding['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        Setting::query()->updateOrCreate(
            ['shop_id' => null, 'setting_key' => self::SETTING_KEY],
            ['value' => $branding, 'is_encrypted' => false],
        );

        return to_route('admin.settings.branding.edit')->with('status', 'Application branding has been updated.');
    }

    /** @return array<string, mixed> */
    private function branding(): array
    {
        return Setting::query()->whereNull('shop_id')->where('setting_key', self::SETTING_KEY)->value('value') ?? [];
    }
}
