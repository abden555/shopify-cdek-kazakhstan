<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="shopify-api-key" content="{{ config('shopify.api_key') }}">
    <title>{{ config('app.name') }}</title>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-card card border-0 shadow-lg text-center">
        <div class="card-body p-4 p-md-5">
            <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
            <h1 class="h4">Connecting Shopify</h1>
            <p class="text-body-secondary mb-0">Your secure store session is being established.</p>
        </div>
    </main>
    <script>
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const token = await window.shopify.idToken();
                const response = await fetch(@json(route('shopify.session.exchange')), {
                    method: 'POST',
                    headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('Session exchange failed.');
                const data = await response.json();
                document.querySelector('main').innerHTML = '<div class="card-body p-4 p-md-5"><div class="text-success fs-1 mb-3">✓</div><h1 class="h4">Shopify connected</h1><p class="text-body-secondary mb-0">' + data.shop.name + ' is ready for secure API requests.</p></div>';
            } catch (error) {
                document.querySelector('main').innerHTML = '<div class="card-body p-4 p-md-5"><h1 class="h4">Unable to connect Shopify</h1><p class="text-body-secondary mb-0">Refresh the page or reinstall the app from Shopify admin.</p></div>';
            }
        });
    </script>
</body>
</html>
