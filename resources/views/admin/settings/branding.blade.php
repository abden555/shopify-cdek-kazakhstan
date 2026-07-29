<x-admin.layout title="Application branding">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="mb-4"><h2 class="h3 mb-1">Application branding</h2><p class="text-body-secondary mb-0">Upload the logo shown in the left navigation header.</p></div>
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="card">
                @csrf @method('PUT')
                <div class="card-body p-4">
                    @if(filled($branding['logo_path'] ?? null))
                        <div class="mb-4"><p class="form-label">Current logo</p><img class="branding-preview" src="{{ asset('storage/'.$branding['logo_path']) }}" alt="Current application logo"><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo"><label class="form-check-label" for="remove_logo">Remove current logo</label></div></div>
                    @endif
                    <label class="form-label" for="logo">New logo</label>
                    <input class="form-control @error('logo') is-invalid @enderror" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    <div class="form-text">PNG, JPG, WebP, or SVG. Maximum file size: 2 MB. A horizontal logo works best.</div>
                    @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="card-footer bg-white border-0 p-4 pt-0"><button class="btn btn-primary">Save logo</button></div>
            </form>
        </div>
    </div>
</x-admin.layout>
