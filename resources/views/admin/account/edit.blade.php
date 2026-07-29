<x-admin.layout title="My account">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="mb-4">
                <h2 class="h3 mb-1">My account</h2>
                <p class="text-body-secondary mb-0">Update your sign-in details and password.</p>
            </div>

            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

            <form method="POST" action="{{ route('admin.account.update') }}" class="card">
                @csrf @method('PUT')
                <div class="card-body p-4">
                    <h3 class="h5 mb-3">Profile details</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name</label>
                            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <hr class="my-4">
                    <h3 class="h5 mb-1">Change password</h3>
                    <p class="text-body-secondary small mb-3">Leave these fields empty if you do not want to change your password.</p>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="current_password">Current password</label><input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-4"><label class="form-label" for="password">New password</label><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-4"><label class="form-label" for="password_confirmation">Confirm new password</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password"></div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-4 pt-0"><button class="btn btn-primary">Save account</button></div>
            </form>
        </div>
    </div>
</x-admin.layout>
