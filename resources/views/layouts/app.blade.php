<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventory Management')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            /* This is a light interface. Without this the browser hands native
               UI — select popups, date pickers, scrollbars — to the OS theme,
               so a Mac or phone in dark mode drops a black dropdown onto a
               white page. */
            color-scheme: light;

            /* Brand — royal warm blue. Used for identity and actions only;
               status meaning lives in the semantic tokens below. */
            --brand: #0033A1;
            --brand-dark: #002A85;
            --brand-darker: #001F63;
            --brand-soft: #E8EDF8;
            --brand-tint: #F4F7FB;
            --brand-ring: rgba(0, 51, 161, 0.18);

            /* Semantic — a stock figure must never read as "brand" */
            --success: #15803D;
            --success-soft: #E8F6ED;
            --success-dark: #116331;
            --coral: #D9704F;
            --coral-soft: #FBE8DE;
            --coral-dark: #B2502F;
            --warning: #B45309;
            --warning-soft: #FDF4E5;
            --warning-dark: #8A4308;
            --critical: #DC2626;
            --critical-soft: #FDECEC;
            --critical-dark: #A81E1E;
            --info: #0E7490;
            --info-soft: #E3F4F8;
            --info-dark: #0A5566;

            /* Neutrals — muted lifted to 4.8:1 on white, was failing AA */
            --text-dark: #16192C;
            --text-muted: #6B7280;
            --border-soft: #E5E8F0;
            --surface: #FFFFFF;

            --page-bg-start: #EDF1FB;
            --page-bg-end: #F8F9FD;

            /* Elevation and radii, so components stop inventing their own */
            --shadow-sm: 0 1px 2px rgba(22,25,44,0.05);
            --shadow-md: 0 4px 16px rgba(22,25,44,0.07);
            --shadow-lg: 0 12px 32px rgba(22,25,44,0.10);
            --r-sm: 10px;
            --r-md: 14px;
            --r-lg: 18px;

            /* Control heights — every button, input and pill snaps to these */
            --ctl-h: 40px;
            --ctl-h-lg: 44px;
            --ctl-h-sm: 32px;

            --sidebar-width: 250px;
            --bottom-bar-height: 62px;
            --topbar-height: 56px;
            --bs-primary: #0033A1;
        }
        * { box-sizing: border-box; }
        /* Nothing in the stylesheet constrained images, so an uploaded logo
           could render wider than its card and push off screen. */
        img { max-width: 100%; height: auto; }
        body {
            background: linear-gradient(170deg, var(--page-bg-start) 0%, var(--page-bg-end) 55%, #FBFCFE 100%);
            background-attachment: fixed;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI Variable Text', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        h1, h2, h3, h4, h5, h6 { letter-spacing: -0.015em; font-weight: 700; color: var(--text-dark); }
        /* Figures align in columns and stop jittering as they change */
        .cl-hero, .cl-value, .stat-number, .li-avail, .fa-summary, .table td { font-variant-numeric: tabular-nums; }

        /* ============ SIDEBAR ============ */
        .sidebar {
            width: var(--sidebar-width);
            min-height: calc(100vh - 24px);
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 8px 30px rgba(31,42,55,0.06);
            position: fixed;
            top: 12px; left: 12px; bottom: 12px;
            z-index: 1050;
            transition: transform 0.3s ease;
            overflow-y: auto;
            padding-bottom: 1rem;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem;
            color: var(--text-dark);
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-brand .brand-icon {
            width: 38px; height: 38px;
            background: var(--brand);
            color: #fff;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .sidebar-brand .brand-logo-img { width: 100%; height: auto; display: block; }
        .sidebar-section {
            color: #B7C0CC;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 1.1rem 1.5rem 0.5rem;
            font-weight: 700;
        }
        .sidebar .nav-link {
            position: relative;
            color: #8993A4;
            padding: 0.6rem 1.25rem 0.6rem 2.1rem;
            margin: 1px 0.75rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 10px; top: 50%;
            transform: translateY(-50%);
            width: 6px; height: 6px;
            border-radius: 50%;
            background: transparent;
            transition: background 0.15s ease;
        }
        .sidebar .nav-link:hover {
            color: var(--brand-dark);
            background: var(--brand-soft);
        }
        .sidebar .nav-link.active {
            color: var(--text-dark);
            font-weight: 700;
            background: transparent;
        }
        .sidebar .nav-link.active::before { background: var(--brand); }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            font-size: 1rem;
        }

        /* ============ MAIN CONTENT ============ */
        .main-content {
            margin-left: calc(var(--sidebar-width) + 24px);
            min-height: 100vh;
        }
        /* Solid, not transparent — content used to scroll visibly underneath */
        .top-navbar {
            background: rgba(255,255,255,0.88);
            backdrop-filter: saturate(180%) blur(12px);
            -webkit-backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid var(--border-soft);
            padding: 0.5rem 1.75rem;
            min-height: var(--topbar-height);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .topbar-title {
            font-weight: 700; font-size: 1.05rem; letter-spacing: -0.01em;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .topbar-menu {
            width: 44px; height: 44px; padding: 0; margin-left: -0.6rem;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: var(--text-dark); text-decoration: none;
        }
        .content-wrapper {
            padding: 0.5rem 1.75rem 2rem;
            max-width: 1500px;
        }

        /* ============ CARDS ============ */
        .card {
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-soft);
            border-radius: var(--r-lg);
        }
        .card-header {
            border-radius: var(--r-lg) var(--r-lg) 0 0 !important;
            font-weight: 700;
            background: #fff;
            border-bottom: 1px solid var(--border-soft) !important;
        }

        /* ============ ACTION CARDS ============ */
        .action-card {
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: inherit;
            min-height: 140px;
            background: #fff;
        }
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(31,42,55,0.1);
            text-decoration: none;
            color: inherit;
        }
        .action-card .action-icon {
            font-size: 1.6rem; margin-bottom: 0.75rem;
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
        }
        .action-card .action-label { font-size: 1.05rem; font-weight: 700; color: var(--text-dark); }
        .action-card .action-hint { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }

        .action-card-green:hover { border-color: var(--brand); }
        .action-card-green .action-icon { color: var(--brand); background: var(--brand-soft); }

        .action-card-blue:hover { border-color: var(--info); }
        .action-card-blue .action-icon { color: var(--info-dark); background: var(--info-soft); }

        .action-card-orange:hover { border-color: var(--warning); }
        .action-card-orange .action-icon { color: var(--warning-dark); background: var(--warning-soft); }

        .action-card-purple:hover { border-color: var(--coral); }
        .action-card-purple .action-icon { color: var(--coral-dark); background: var(--coral-soft); }

        /* ============ STAT CARDS ============ */
        .stat-card { border-radius: 18px; text-align: left; padding: 0.25rem; }
        .stat-card .stat-icon {
            width: 46px; height: 46px; border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 0.75rem;
        }
        .stat-card .stat-number { font-size: 1.9rem; font-weight: 700; line-height: 1.2; color: var(--text-dark); }
        .stat-card .stat-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
        /* A rupee figure can run long; shrink to fit one line rather than
           breaking mid-number, which reads as a typo rather than a wrap. */
        .stat-card .stat-number-money { font-size: clamp(1.15rem, 4.5vw, 1.9rem); white-space: nowrap; }
        .stat-card .stat-label .bi-info-circle { font-size: 0.78rem; color: var(--text-muted); cursor: help; }

        /* ============ STATUS BADGES (soft colors for readability) ============ */
        .badge { font-weight: 600; font-size: 0.72rem; padding: 0.32em 0.65em; border-radius: 999px; }
        .badge-pending { background-color: var(--coral-soft); color: var(--coral-dark); border: none; }
        .badge-dispatched { background-color: var(--success-soft); color: var(--success-dark); border: none; }
        .badge-cancelled { background-color: var(--critical-soft); color: var(--critical-dark); border: none; }
        .badge-completed { background-color: var(--success-soft); color: var(--success-dark); border: none; }
        .badge-rejected { background-color: var(--critical-soft); color: var(--critical-dark); border: none; }
        .badge-overdue { background-color: var(--critical-soft); color: var(--critical-dark); border: none; animation: pulse-badge 2s infinite; }
        .badge-today { background-color: var(--warning-soft); color: var(--warning-dark); border: none; font-weight: 700; }

        @keyframes pulse-badge { 0%,100%{opacity:1;} 50%{opacity:0.6;} }

        /* ============ STOCK LEVEL COLORS ============ */
        .stock-ok { color: var(--success); font-weight: 600; }
        .stock-low { color: var(--warning-dark); font-weight: 700; }
        .stock-critical { color: var(--critical-dark); font-weight: 700; }
        .stock-zero { color: var(--text-muted); font-weight: 600; }
        .row-stock-low { background-color: var(--warning-soft) !important; }
        .row-stock-critical { background-color: var(--critical-soft) !important; }
        .row-stock-zero { background-color: #F5F6F8 !important; }

        /* ============ TABLES ============ */
        .table th {
            font-weight: 700; font-size: 0.72rem; text-transform: uppercase;
            color: var(--text-muted); letter-spacing: 0.4px; background-color: #FAFCFB;
            padding: 0.85rem 0.9rem; white-space: nowrap; border-bottom: 1px solid var(--border-soft);
        }
        .table td { padding: 0.75rem 0.9rem; vertical-align: middle; font-size: 0.9rem; border-color: var(--border-soft); white-space: nowrap; }
        .table-hover tbody tr:hover { background-color: var(--brand-soft) !important; }
        .table td.wrap, .table th.wrap { white-space: normal; }

        /* Card-list (mobile alternative to wide tables) */
        .card-list-item {
            background: #fff; border: 1px solid var(--border-soft); border-radius: 14px;
            padding: 0.85rem 1rem; margin-bottom: 0.6rem;
        }
        .card-list-item .cl-row { display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; }
        .card-list-item .cl-label { font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.3px; }
        .card-list-item .cl-value { font-size: 0.88rem; font-weight: 600; color: var(--text-dark); text-align: right; }
        /* Headline figure on stock cards — the answer, not a table cell */
        .card-list-item .cl-hero { font-size: 1.7rem; font-weight: 800; line-height: 1.05; }

        /* ============ LINE-ITEM EDITOR ============ */
        /* Shared by the receive / dispatch / transfer / correction forms.
           Each row is a self-contained block that reflows to one column on a
           phone, so the quantity field is never behind a horizontal scroll. */
        .line-item {
            display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0.6rem 1rem;
            background: #fff; border: 1px solid var(--border-soft); border-radius: var(--r-md);
            padding: 0.75rem 0.85rem; margin-bottom: 0.5rem;
            transition: background-color 0.8s ease, border-color 0.2s ease;
        }
        .line-item-new { background-color: var(--brand-soft); }
        .line-item-invalid { border-color: var(--critical); background-color: var(--critical-soft); }
        .line-item .li-main { flex: 1 1 240px; min-width: 0; }
        .line-item .li-code { font-size: 0.72rem; font-weight: 700; color: var(--brand-dark); }
        .line-item .li-name { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); word-break: break-word; }
        .line-item .li-avail { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.1rem; }
        /* Quantity and remove share a row aligned on their bottom edge, so the
           button lines up with the input rather than the label above it. */
        .line-item .li-controls { display: flex; align-items: flex-end; gap: 0.5rem; flex: 0 1 260px; }
        .line-item .li-qty { flex: 1 1 auto; min-width: 0; }
        /* Receipts ask for a price too, so the controls need room for two fields */
        .line-items-priced .line-item .li-controls { flex-basis: 420px; }
        .line-item .li-qty, .line-item .li-price { flex: 1 1 0; min-width: 0; }
        /* Dispatches also ask for an HSN code — three fields need more room,
           and wrap onto their own row on narrow screens rather than squeezing. */
        .line-items-hsn .line-item .li-controls { flex-basis: 540px; flex-wrap: wrap; }
        /* Real flex bases, so the row can actually wrap: with a 0 basis the
           three inputs never overflowed the line and just squeezed to a few
           pixels each on a phone. */
        .line-items-hsn .line-item .li-qty, .line-items-hsn .line-item .li-price { flex: 1 1 130px; }
        .line-item .li-hsn { flex: 0 1 110px; min-width: 90px; }
        .line-items-hsn .line-item .li-hsn { flex: 1 1 110px; }
        .line-item .li-amount strong { color: var(--text-dark); }
        .line-item .cl-label {
            display: block; margin-bottom: 0.2rem; font-size: 0.68rem; text-transform: uppercase;
            color: var(--text-muted); font-weight: 700; letter-spacing: 0.3px;
        }
        .line-item .li-remove { flex: 0 0 auto; }
        .line-item .li-error { flex: 1 1 100%; margin-top: -0.2rem; font-size: 0.8rem; }

        /* Icon-only button — explicit opt-in so text buttons are never squeezed */
        .btn-icon { width: 44px; min-width: 44px; height: 44px; min-height: 44px; padding: 0; }

        /* Product picker results */
        .sku-result { min-height: 48px; padding: 0.6rem 1rem !important; text-align: left; }
        .sku-result .sku-result-top { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .sku-result .sku-result-top code { font-size: 0.78rem; font-weight: 700; color: var(--brand-dark); }
        .sku-result-cat { font-size: 0.72rem; color: var(--text-muted); }
        .sku-result-name { font-size: 0.88rem; color: var(--text-dark); }
        .sku-result.active { background-color: var(--brand-soft); }
        .sku-result-added { opacity: 0.55; }
        .sku-result-flag { font-size: 0.72rem; color: var(--text-muted); }
        .sku-result-more {
            padding: 0.5rem 1rem; font-size: 0.75rem; color: var(--text-muted);
            text-align: center; background: #FAFCFB;
        }

        /* Sticky save bar, so the primary action never scrolls out of reach */
        .form-action-bar {
            position: sticky; bottom: 0; z-index: 5;
            background: rgba(255,255,255,0.97);
            border-top: 1px solid var(--border-soft);
            padding: 0.75rem 0; margin-top: 1rem;
            display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
        }
        .form-action-bar .fa-summary { font-size: 0.82rem; color: var(--text-muted); margin-left: auto; }

        /* ============ BUTTONS ============ */
        /* One control scale. Height comes from min-height so every control
           lines up; padding and type stay compact rather than inflating it. */
        .btn {
            font-weight: 600; border-radius: var(--r-sm);
            display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
            line-height: 1.2;
            min-height: var(--ctl-h);
            padding: 0.4rem 0.95rem;
            font-size: 0.9rem;
        }
        .btn-primary { background-color: var(--brand); border-color: var(--brand); }
        .btn-primary:hover { background-color: var(--brand-dark); border-color: var(--brand-dark); }
        .btn-outline-secondary { color: var(--text-muted); border-color: var(--border-soft); background-color: #fff; }
        .btn-outline-secondary:hover { background-color: #F4F5F9; border-color: var(--border-soft); color: var(--text-dark); }
        .btn-lg { min-height: var(--ctl-h-lg); padding: 0.5rem 1.25rem; font-size: 0.95rem; }
        .btn-sm { min-height: var(--ctl-h-sm); padding: 0.25rem 0.7rem; font-size: 0.82rem; }

        /* Semantic fills. They inherit sizing from .btn above — there is no
           second, larger set of "action" buttons any more. */
        .btn-danger { background-color: var(--critical); border-color: var(--critical); }
        .btn-danger:hover { background-color: var(--critical-dark); border-color: var(--critical-dark); }
        .btn-success { background-color: var(--success); border-color: var(--success); }
        .btn-success:hover { background-color: var(--success-dark); border-color: var(--success-dark); }
        .btn-outline-primary { color: var(--brand); border-color: var(--brand); }
        .btn-outline-primary:hover { background-color: var(--brand); border-color: var(--brand); color: #fff; }

        /* ============ FORMS ============ */
        .form-label { font-weight: 600; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.3rem; }
        .form-control, .form-select {
            border-radius: var(--r-sm); padding: 0.4rem 0.75rem; font-size: 0.92rem;
            min-height: var(--ctl-h);
            border-color: var(--border-soft);
            background-color: #FBFCFE;
            color: var(--text-dark);
        }
        /* The padding shorthand above wiped Bootstrap's asymmetric padding, which
           left long option text free to run under the dropdown chevron. */
        .form-select { padding-right: 2.25rem; font-size: 1rem; }

        /* ============ DROPDOWNS ============ */
        /* public/js/select.js swaps the OS-drawn popup for this listbox, so the
           list is legible and identical on every platform. The <select> itself
           stays put and keeps holding the value. */
        [hidden] { display: none !important; }

        .select-ui { position: relative; }
        /* Visually hidden but still focusable — display:none on a required
           field makes it unfocusable and silently blocks form submission. */
        .select-ui > select {
            position: absolute; inset: 0; width: 100%; height: 100%;
            opacity: 0; pointer-events: none; z-index: -1;
        }
        .select-ui-toggle {
            width: 100%; text-align: left; cursor: pointer;
            display: flex; align-items: center;
        }
        .select-ui-toggle > span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .select-ui-toggle.is-placeholder { color: var(--text-muted); }
        .select-ui-toggle:disabled { background-color: #F1F3F7; cursor: not-allowed; }
        .select-ui.is-open .select-ui-toggle { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-ring); }

        .select-ui-menu {
            position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 1060;
            background: #fff; border: 1px solid var(--border-soft);
            border-radius: var(--r-md); box-shadow: var(--shadow-lg);
            padding: 0.3rem; max-height: 280px; overflow-y: auto;
        }
        .select-ui.drop-up .select-ui-menu { top: auto; bottom: calc(100% + 6px); }

        .select-ui-option {
            display: flex; align-items: center; width: 100%;
            min-height: 44px; padding: 0.5rem 0.75rem;
            background: none; border: 0; border-radius: var(--r-sm);
            font-size: 0.95rem; text-align: left; color: var(--text-dark); cursor: pointer;
        }
        .select-ui-option:hover, .select-ui-option:focus { background: var(--brand-soft); outline: none; }
        .select-ui-option.is-selected { background: var(--brand); color: #fff; font-weight: 600; }
        .select-ui-option:disabled { color: var(--text-muted); cursor: not-allowed; background: none; }
        .select-ui-backdrop { display: none; }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-ring); background-color: #fff;
        }
        .form-control::placeholder { color: #9AA1AE; }
        /* Unit suffixes read as a quiet label, not a second control */
        .input-group-text {
            background: transparent; border-color: var(--border-soft);
            color: var(--text-muted); font-size: 0.82rem; font-weight: 600;
            padding: 0.4rem 0.7rem;
        }
        .input-group .form-control:focus + .input-group-text { border-color: var(--brand); }
        .form-control.is-invalid { border-color: var(--critical); box-shadow: 0 0 0 3px rgba(217,83,79,0.14); }
        .invalid-feedback { font-size: 0.85rem; font-weight: 500; }
        .form-hint { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem; }
        .required::after { content: ' *'; color: var(--critical); font-weight: 700; }

        /* ============ STEP INDICATOR ============ */
        .step-indicator { display: flex; margin-bottom: 1.5rem; counter-reset: step; }
        .step-indicator .step {
            flex: 1; text-align: center; padding: 0.75rem 0.5rem; position: relative;
            background: #F5F6F8; font-size: 0.85rem; font-weight: 600; color: var(--text-muted);
        }
        .step-indicator .step:first-child { border-radius: 10px 0 0 10px; }
        .step-indicator .step:last-child { border-radius: 0 10px 10px 0; }
        .step-indicator .step.active { background: var(--brand-soft); color: var(--brand-dark); }
        .step-indicator .step.done { background: var(--brand-soft); color: var(--brand-dark); }
        .step-indicator .step::before { counter-increment: step; content: counter(step) ". "; font-weight: 700; }

        /* ============ SECTION DIVIDER ============ */
        .section-divider { border: none; border-top: 1px solid var(--border-soft); margin: 1.5rem 0; }

        /* ============ EMPTY STATE ============ */
        .empty-state { text-align: center; padding: 3rem 2rem; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 0.75rem; display: block; opacity: 0.5; }
        .empty-state .empty-text { font-size: 1.05rem; font-weight: 500; }
        .empty-state .empty-hint { font-size: 0.85rem; margin-top: 0.3rem; }

        /* ============ CONFIRMATION MODAL ============ */
        .confirm-modal-body { text-align: center; padding: 1.5rem; }
        .confirm-modal-body .confirm-icon { font-size: 3.5rem; margin-bottom: 0.75rem; }
        .confirm-modal-body h5 { font-weight: 700; margin-bottom: 0.5rem; }
        .confirm-modal-body p { color: var(--text-muted); font-size: 0.95rem; }
        .modal-content { border-radius: 18px; border: none; }

        /* ============ INFO STRIP ============ */
        .info-strip {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem; padding: 1rem 0;
        }
        .info-strip .info-item { padding: 0.5rem 0; }
        .info-strip .info-label {
            font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted);
            font-weight: 700; letter-spacing: 0.5px;
        }
        .info-strip .info-value { font-size: 1rem; font-weight: 600; color: var(--text-dark); margin-top: 2px; }

        /* ============ TOAST ============ */
        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 1090; }
        .toast { border-radius: 14px; font-weight: 500; }

        /* ============ SKU SEARCH DROPDOWN ============ */
        .sku-search-results {
            position: absolute; z-index: 1000; max-height: 250px; overflow-y: auto; width: 100%;
            box-shadow: 0 10px 28px rgba(31,42,55,0.12); border-radius: 14px; display: none;
        }
        .sku-search-results .list-group-item {
            padding: 0.65rem 1rem; font-size: 0.9rem; cursor: pointer; border-left: none; border-right: none;
        }
        .sku-search-results .list-group-item:hover { background-color: var(--brand-soft); }

        /* ============ TAP TARGETS ============ */
        /* Desktop runs the compact scale above; the mobile block lifts every
           interactive control to 44px, where thumbs actually matter. */
        /* Buttons inside tables keep their labels. Only an explicit .btn-icon is
           square — forcing every .table .btn to 34px clipped "Process",
           "View" and "View & Accept" on five index pages. */
        .table .btn { min-height: var(--ctl-h-sm); padding: 0.25rem 0.7rem; font-size: 0.82rem; }
        .table .btn.btn-icon { padding: 0; }
        .table .btn:not(:last-child) { margin-right: 0.3rem; }

        /* ============ PAGE HEADER ============ */
        .min-width-0 { min-width: 0; }
        .page-header { margin-bottom: 1.5rem; }
        .page-header h4 { font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem; }
        .page-header .page-subtitle { color: var(--text-muted); font-size: 0.9rem; }

        /* ============ MOBILE BOTTOM TAB BAR ============ */
        .bottom-tab-bar {
            display: none;
            position: fixed; left: 0; right: 0; bottom: 0;
            background: #fff;
            border-top: 1px solid var(--border-soft);
            box-shadow: 0 -4px 20px rgba(31,42,55,0.06);
            /* Below the drawer and its overlay, so an open menu covers it */
            z-index: 1040;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
        .bottom-tab-bar .tab-row { display: flex; height: var(--bottom-bar-height); }
        .bottom-tab-bar .tab-item {
            position: relative;
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 2px; color: var(--text-muted); text-decoration: none; font-size: 0.68rem; font-weight: 600;
            background: none; border: none;
        }
        .bottom-tab-bar .tab-item i { font-size: 1.25rem; }
        .bottom-tab-bar .tab-item.active { color: var(--brand); }

        /* Centre create button — raised out of the bar */
        .fab-circle {
            width: 50px; height: 50px; border-radius: 17px; margin-top: -20px;
            background: var(--brand); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.45rem; box-shadow: 0 8px 18px rgba(0,51,161,0.34);
        }
        .tab-fab:active .fab-circle { transform: scale(0.94); }
        .tab-badge {
            position: absolute; top: 6px; left: 50%; margin-left: 4px;
            background: var(--critical); color: #fff;
            font-size: 0.62rem; font-weight: 700; line-height: 1;
            min-width: 17px; height: 17px; border-radius: 9px; padding: 0 4px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        /* Segmented tabs (Dispatch: To send / Mine / History) */
        .seg-tabs { gap: 0.4rem; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 2px; }
        .seg-tabs .nav-link {
            white-space: nowrap; border-radius: 999px; font-weight: 600; font-size: 0.84rem;
            /* Overrides the global 44px nav-link floor — these are filters,
               not primary tap targets, and 44px made them look like slabs. */
            min-height: 34px; padding: 0.3rem 0.8rem;
            display: inline-flex; align-items: center;
            color: var(--text-muted); background: #fff;
            border: 1px solid var(--border-soft);
        }
        .seg-tabs .nav-link.active { background: var(--brand); border-color: var(--brand); color: #fff; }

        .nav-count {
            margin-left: auto; background: var(--critical); color: #fff;
            font-size: 0.68rem; font-weight: 700; line-height: 1;
            min-width: 20px; height: 20px; border-radius: 10px; padding: 0 6px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .modal-sheet .modal-content { border: none; border-radius: var(--r-lg); }
        .sheet-action {
            justify-content: flex-start !important; gap: 0.75rem;
            padding: 0.85rem 1rem; font-size: 1rem; text-align: left;
        }
        .sheet-action i { font-size: 1.15rem; color: var(--brand); }

        /* ============ MOBILE ============ */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-115%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding-bottom: calc(var(--bottom-bar-height) + env(safe-area-inset-bottom, 0) + 12px); }
            .sidebar-overlay {
                display: none; position: fixed; top:0;left:0;right:0;bottom:0;
                background: rgba(20,30,25,0.45); z-index: 1045;
            }
            .sidebar-overlay.show { display: block; }
            .top-navbar { padding: 0.35rem 0.75rem; }
            /* The page name is in the sticky bar now — don't say it twice */
            .page-header h4 { display: none; }
            .page-header { margin-bottom: 0.9rem; }
            .page-header .page-subtitle { font-size: 0.86rem; margin-bottom: 0; }
            /* With the heading in the sticky bar, the page action takes the
               full width — the same on every screen. */
            .page-header-actions { flex: 1 1 100%; }
            .page-header-actions .btn { flex: 1 1 auto; }
            .card { border-radius: var(--r-md); }
            .card-header { border-radius: var(--r-md) var(--r-md) 0 0 !important; }
            .card-list-item { border-radius: var(--r-md); box-shadow: var(--shadow-sm); }
            /* Four full-width stat cards ran to ~680px of scroll on their own */
            .stat-card .card-body { padding: 0.85rem; }
            .stat-card .stat-icon { width: 34px; height: 34px; font-size: 1rem; border-radius: 10px; margin-bottom: 0.4rem; }
            .stat-card .stat-number { font-size: 1.5rem; }
            .stat-card .stat-label { font-size: 0.75rem; line-height: 1.25; }

            /* Item tables on document pages reflow into stacked rows instead of
               scrolling sideways. Cells pick up their heading from data-label. */
            .table-stack thead { display: none; }
            .table-stack, .table-stack tbody, .table-stack tr, .table-stack td { display: block; width: 100%; }
            .table-stack tr {
                border: 1px solid var(--border-soft); border-radius: var(--r-md);
                margin-bottom: 0.6rem; padding: 0.6rem 0.85rem; background: #fff;
            }
            .table-stack td {
                border: none !important; padding: 0.2rem 0 !important;
                white-space: normal !important;
                display: flex; justify-content: space-between; align-items: baseline; gap: 1rem;
            }
            .table-stack td::before {
                content: attr(data-label);
                font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.3px;
                font-weight: 700; color: var(--text-muted); flex: 0 0 auto;
            }
            .table-stack td:not([data-label]) { justify-content: flex-start; }
            .table-stack tfoot { display: block; }
            .table-stack tfoot td { font-weight: 700; }
            .content-wrapper { padding: 0.5rem 1rem 1.5rem; }
            .action-card { min-height: 110px; padding: 1rem; }
            .action-card .action-icon { font-size: 1.3rem; width: 42px; height: 42px; }
            .bottom-tab-bar { display: block; }
            .d-desktop-table { display: none !important; }
            /* Anything under 16px makes iOS zoom in on focus — the guest layout
               already got this right, so match it here. */
            .form-control, .form-select, input, textarea, select { font-size: 16px; }
            /* One 44px floor for everything tappable, so controls line up */
            .form-control, .form-select,
            .btn, .btn-sm, .btn-lg, .btn-primary, .btn-danger,
            .table .btn { min-height: var(--ctl-h-lg); }
            .card-body { padding: 0.9rem; }
            /* Dropdowns open as a bottom sheet — easier to reach with a thumb
               than a small list pinned under the control. */
            .select-ui-backdrop {
                display: block; position: fixed; inset: 0;
                background: rgba(20,25,40,.45); z-index: 1060;
            }
            .select-ui-menu, .select-ui.drop-up .select-ui-menu {
                position: fixed; top: auto; bottom: 0; left: 0; right: 0;
                max-height: 62vh; z-index: 1061;
                border-radius: var(--r-lg) var(--r-lg) 0 0;
                padding: 0.5rem 0.5rem calc(0.5rem + env(safe-area-inset-bottom, 0px));
                box-shadow: 0 -12px 32px rgba(22,25,44,.18);
            }
            .select-ui-option { min-height: 48px; font-size: 1rem; }

            /* Keep the three steps on one line instead of wrapping to two */
            .step-indicator { margin-bottom: 1rem; }
            .step-indicator .step { font-size: 0.7rem; padding: 0.45rem 0.2rem; }
            /* Keep the sticky save bar clear of the fixed bottom tab bar */
            /* One row of controls, not two — this bar is pinned above the tab
               bar, so every extra row is permanently lost screen space. */
            .form-action-bar {
                bottom: calc(var(--bottom-bar-height) + env(safe-area-inset-bottom, 0px));
                padding: 0.5rem 0 0.55rem; gap: 0.5rem; flex-wrap: wrap;
            }
            .form-action-bar .fa-summary {
                order: -1; flex: 0 0 100%; margin: 0 0 0.15rem; text-align: right; font-size: 0.72rem;
            }
            .form-action-bar .btn-outline-secondary { flex: 0 0 auto; }
            .form-action-bar .btn-primary,
            .form-action-bar .btn-success,
            .form-action-bar .btn-danger { flex: 1 1 auto; min-width: 0; }
        }
        @media (min-width: 992px) {
            .d-mobile-cards { display: none !important; }
        }
        @media (max-width: 575.98px) {
            .stat-card .stat-number { font-size: 1.5rem; }
            .content-wrapper { padding: 0.5rem 0.75rem 1.25rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            @if($logo = \App\Models\Setting::get('company_logo'))
                <img src="{{ asset('storage/' . $logo) }}" alt="{{ \App\Models\Setting::get('company_name', 'Company') }}" class="brand-logo-img">
            @else
                <span class="brand-icon"><i class="bi bi-box-seam-fill"></i></span>
                <span>{{ \App\Models\Setting::get('company_name', 'Inventory Mgmt') }}</span>
            @endif
        </div>
        <div class="nav flex-column mt-1">
            @auth
                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-house-door-fill"></i> Home
                </a>
                {{-- Daily work, in the order it happens. "Check Stock" and
                     "Current Stock" answered the same question, and "Dispatches"
                     / "Send Out Stock" / "Dispatch History" were three views of
                     one table — each pair now has a single entry. --}}
                <div class="sidebar-section">Daily</div>
                <a class="nav-link {{ request()->is('stock-search*') || request()->is('reports/stock-as-on-date*') ? 'active' : '' }}" href="{{ route('stock-search') }}">
                    <i class="bi bi-box-seam-fill"></i> Stock
                </a>
                <a class="nav-link {{ request()->is('grn*') ? 'active' : '' }}" href="{{ route('grn.index') }}">
                    <i class="bi bi-box-arrow-in-down-right"></i> Receive
                </a>
                <a class="nav-link {{ request()->is('dispatch-sheets*') || request()->is('fulfillment*') || request()->is('reports/dispatch-register*') ? 'active' : '' }}" href="{{ route('dispatch-sheets.index') }}">
                    <i class="bi bi-truck"></i> Dispatch
                    @if(($pendingDispatchCount ?? 0) > 0)
                        <span class="nav-count">{{ $pendingDispatchCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->is('stock-transfers*') ? 'active' : '' }}" href="{{ route('stock-transfers.index') }}">
                    <i class="bi bi-arrow-left-right"></i> Transfers
                </a>
                <a class="nav-link {{ request()->is('stock-adjustments*') ? 'active' : '' }}" href="{{ route('stock-adjustments.index') }}">
                    <i class="bi bi-pencil-square"></i> Corrections
                </a>

                <div class="sidebar-section">Records &amp; Setup</div>
                <a class="nav-link {{ request()->is('reports/stock-ledger*') ? 'active' : '' }}" href="{{ route('reports.stock-ledger') }}">
                    <i class="bi bi-book-fill"></i> Movements
                </a>
                <a class="nav-link {{ request()->is('skus*') ? 'active' : '' }}" href="{{ route('skus.index') }}">
                    <i class="bi bi-box-seam"></i> Products
                </a>
                <a class="nav-link {{ request()->is('godowns*') ? 'active' : '' }}" href="{{ route('godowns.index') }}">
                    <i class="bi bi-building-fill"></i> Godowns
                </a>
                @if(auth()->user()->isAdmin())
                    <a class="nav-link {{ request()->is('settings*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">
                        <i class="bi bi-gear-fill"></i> Settings
                    </a>
                    <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                        <i class="bi bi-people-fill"></i> Users
                    </a>
                    <a class="nav-link {{ request()->is('activity-log*') ? 'active' : '' }}" href="{{ route('activity-log.index') }}">
                        <i class="bi bi-clock-history"></i> Activity Log
                    </a>
                @endif
            @endauth
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center min-width-0 flex-grow-1">
                <button class="btn btn-link topbar-menu d-lg-none" onclick="toggleSidebar()"
                        id="menuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
                    <i class="bi bi-list"></i>
                </button>
                {{-- Desktop keeps the full trail. On a phone the breadcrumb just
                     repeated the page heading below it, so show the page name
                     once, here, where it stays visible while scrolling. --}}
                <nav aria-label="breadcrumb" class="mb-0 d-none d-lg-block">
                    <ol class="breadcrumb mb-0" style="font-size:0.88rem;">
                        @yield('breadcrumb')
                    </ol>
                </nav>
                <span class="topbar-title d-lg-none">@yield('title')</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                @auth
                    {{-- My Account left the sidebar in the consolidation; it lives
                         here now, which is where people look for it anyway. --}}
                    <a href="{{ route('account.edit') }}" class="d-none d-sm-inline text-muted text-decoration-none" style="font-size:0.88rem;">
                        {{ auth()->user()->name }}
                    </a>
                    <a href="{{ route('account.edit') }}" class="d-sm-none topbar-menu" aria-label="My account">
                        <i class="bi bi-person-circle" style="font-size:1.25rem;"></i>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-right"></i> <span class="d-none d-sm-inline">Logout</span>
                        </button>
                    </form>
                @endauth
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- Mobile Bottom Tab Bar -->
    @auth
    <nav class="bottom-tab-bar">
        <div class="tab-row">
            <a href="{{ route('dashboard') }}" class="tab-item {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-door-fill"></i> Home
            </a>
            <a href="{{ route('stock-search') }}" class="tab-item {{ request()->is('stock-search*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Stock
            </a>
            {{-- Creating things had no home in the nav at all — "New Dispatch"
                 existed only as a dashboard card. This puts all four one tap away. --}}
            <button type="button" class="tab-item tab-fab" data-bs-toggle="modal" data-bs-target="#createSheet" aria-label="Create new">
                <span class="fab-circle"><i class="bi bi-plus-lg"></i></span>
            </button>
            <a href="{{ route('dispatch-sheets.index') }}" class="tab-item {{ request()->is('dispatch-sheets*') || request()->is('fulfillment*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Dispatch
                @if(($pendingDispatchCount ?? 0) > 0)
                    <span class="tab-badge">{{ $pendingDispatchCount }}</span>
                @endif
            </a>
            <a href="#" class="tab-item" onclick="event.preventDefault(); toggleSidebar();">
                <i class="bi bi-grid-3x3-gap-fill"></i> More
            </a>
        </div>
    </nav>

    {{-- Create sheet, opened by the + tab --}}
    <div class="modal fade" id="createSheet" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sheet">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Create new</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-grid gap-2">
                    <a href="{{ route('grn.create') }}" class="btn btn-outline-secondary sheet-action">
                        <i class="bi bi-box-arrow-in-down-right"></i> Receive Stock
                    </a>
                    <a href="{{ route('dispatch-sheets.create') }}" class="btn btn-outline-secondary sheet-action">
                        <i class="bi bi-file-earmark-plus"></i> New Dispatch
                    </a>
                    <a href="{{ route('stock-transfers.create') }}" class="btn btn-outline-secondary sheet-action">
                        <i class="bi bi-arrow-left-right"></i> Transfer Stock
                    </a>
                    <a href="{{ route('stock-adjustments.create') }}" class="btn btn-outline-secondary sheet-action">
                        <i class="bi bi-pencil-square"></i> Stock Correction
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer">
        @if(session('success'))
            <div class="toast show align-items-center text-bg-success border-0" role="alert" data-bs-autohide="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="toast show align-items-center text-bg-danger border-0" role="alert" data-bs-autohide="true" data-bs-delay="6000">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
        @if(session('warning'))
            <div class="toast show align-items-center text-bg-warning border-0" role="alert" data-bs-autohide="true" data-bs-delay="5000">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('warning') }}</div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/select.js') }}?v={{ filemtime(public_path('js/select.js')) }}"></script>
    <script src="{{ asset('js/filter-autosubmit.js') }}?v={{ filemtime(public_path('js/filter-autosubmit.js')) }}"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        // Auto-hide toasts
        document.querySelectorAll('.toast[data-bs-autohide="true"]').forEach(function(el) {
            var toast = new bootstrap.Toast(el);
            toast.show();
        });
        // Close SKU search dropdown when clicking outside
        document.addEventListener('click', function(e) {
            var results = document.getElementById('skuSearchResults');
            var input = document.getElementById('skuSearchInput');
            if (results && input && !results.contains(e.target) && e.target !== input) {
                results.style.display = 'none';
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
