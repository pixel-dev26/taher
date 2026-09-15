<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            /* Light interface — keeps native controls out of the OS dark theme */
            color-scheme: light;
            --brand: #0033A1;
            --brand-dark: #002A85;
            --brand-soft: #E8EDF8;
            --text-dark: #16192C;
            --text-muted: #6B7280;
            --border-soft: #E5E8F0;
        }
        body {
            background: linear-gradient(170deg, #EDF1FB 0%, #F8F9FD 55%, #FBFCFE 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-dark);
        }
        .login-card { max-width: 440px; width: 100%; margin: 1rem; }
        .login-card .card {
            border-radius: 24px; border: 1px solid var(--border-soft);
            box-shadow: 0 20px 50px rgba(31,42,55,0.08);
        }
        .login-card .card-body { padding: 2.75rem 2.25rem; }
        .brand-icon {
            width: 56px; height: 56px; margin: 0 auto;
            background: var(--brand); color: #fff;
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
        }
        .form-label { font-weight: 600; font-size: 0.88rem; color: var(--text-dark); }
        .form-control {
            border-radius: 12px; padding: 0.6rem 0.85rem 0.6rem 2.5rem; font-size: 1rem;
            min-height: 48px; border-color: var(--border-soft); background-color: #FAFBFE;
        }
        .form-control:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(0,51,161,0.18); background-color: #fff; }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap i {
            position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 1rem; z-index: 5;
        }
        .btn { min-height: 48px; border-radius: 12px; font-weight: 700; font-size: 1rem; }
        .btn-primary { background-color: var(--brand); border-color: var(--brand); }
        .btn-primary:hover { background-color: var(--brand-dark); border-color: var(--brand-dark); }
        a { color: var(--brand-dark); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    @if($logo = \App\Models\Setting::get('company_logo'))
                        <img src="{{ asset('storage/' . $logo) }}" alt="{{ \App\Models\Setting::get('company_name', 'Company') }}" style="max-width: 100%; height: auto;">
                    @else
                        <div class="brand-icon"><i class="bi bi-box-seam-fill"></i></div>
                        <h4 class="mt-3 mb-1" style="color: var(--text-dark); font-weight: 700;">{{ \App\Models\Setting::get('company_name', 'Inventory Management') }}</h4>
                    @endif
                    <p class="mb-0 mt-2" style="font-size: 0.9rem; color: var(--text-muted);">Sign in to continue</p>
                </div>
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
