<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AccountController extends Controller
{
    public function edit(): View
    {
        return view('admin.account.edit');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'password']);
        $request->user()->fill(array_filter($data, static fn (mixed $value): bool => $value !== null))->save();

        return to_route('admin.account.edit')->with('status', 'Your account details have been updated.');
    }
}
