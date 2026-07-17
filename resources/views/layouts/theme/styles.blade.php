<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<base href="{{ url('/') }}/">

<link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/png" />

<link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />

<link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />
<script src="{{ asset('assets/js/pace.min.js') }}"></script>

<link href="{{ asset('assets/css/bootstrap.min.css') }}?v={{ filemtime(public_path('assets/css/bootstrap.min.css')) }}" rel="stylesheet">
<link href="{{ asset('assets/css/bootstrap-extended.css') }}?v={{ filemtime(public_path('assets/css/bootstrap-extended.css')) }}" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

<link href="{{ asset('assets/css/app.css') }}?v={{ filemtime(public_path('assets/css/app.css')) }}" rel="stylesheet">
<link href="{{ asset('assets/css/icons.css') }}?v={{ filemtime(public_path('assets/css/icons.css')) }}" rel="stylesheet">
<link href="{{ asset('assets/plugins/flatpickr/flatpickr.css') }}" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}?v={{ filemtime(public_path('assets/css/header-colors.css')) }}" />

@livewireStyles

<style>
/* Lenguaje visual común para módulos, formularios y listados */
.page-content{--module-border:#e2e8f0;--module-muted:#667085;--module-soft:#f7f9fc;--module-surface:#fff;--module-heading:#263238}
.page-content .module-header{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.25rem}
.page-content .module-header h4{color:var(--module-heading);font-weight:600}
.page-content .module-counter{display:inline-flex;align-items:center;min-height:34px;padding:.35rem .75rem;border:1px solid var(--module-border);border-radius:999px;background:var(--module-surface);color:var(--module-muted);font-size:.82rem;font-weight:500;white-space:nowrap}
.page-content>.card,.page-content .row>.col-lg-7>.card,.page-content .row>.col-lg-5>.card{border:1px solid var(--module-border);border-radius:.75rem;box-shadow:0 3px 14px rgba(31,54,78,.045);overflow:visible}
.page-content .card-header{display:flex;justify-content:space-between;align-items:center;gap:1rem;min-height:58px;padding:.8rem 1rem;background:var(--module-soft);border-bottom:1px solid var(--module-border);border-radius:.75rem .75rem 0 0}
.page-content .card-header strong,.page-content .card-header .fw-semibold{color:var(--module-heading);font-size:.96rem}
.page-content .module-form-card{border-top:3px solid var(--bs-primary,#0d6efd)!important;scroll-margin-top:82px}
.page-content .module-form-card>.card-header{background:linear-gradient(90deg,rgba(13,110,253,.07),transparent)}
.page-content .form-card-subtitle{margin-top:.18rem;color:var(--module-muted);font-size:.78rem;font-weight:400}
.page-content .form-section-title{margin-bottom:.75rem;color:var(--module-heading);font-size:.84rem;font-weight:600;text-transform:uppercase;letter-spacing:.035em}
.page-content form .form-label,.page-content .filter-label{display:block;margin-bottom:.4rem;color:#475467;font-size:.82rem;font-weight:600}
.page-content .field-optional{margin-left:.25rem;color:#98a2b3;font-size:.7rem;font-weight:400}
.page-content form .form-control:not(textarea):not([type=file]),.page-content form .form-select:not([multiple]),.page-content .filter-header .form-control,.page-content .filter-header .form-select{min-height:41px}
.page-content form .form-control,.page-content form .form-select,.page-content .filter-header .form-control,.page-content .filter-header .form-select{border-color:#d0d5dd;border-radius:.5rem}
.page-content form .form-control:focus,.page-content form .form-select:focus,.page-content .filter-header .form-control:focus,.page-content .filter-header .form-select:focus{border-color:#86b7fe;box-shadow:0 0 0 .2rem rgba(13,110,253,.12)}
.page-content form .invalid-feedback{font-size:.76rem}
.page-content .readonly-control{height:auto;min-height:41px;background:var(--module-soft);color:var(--module-heading)}
.page-content .form-actions{display:flex;justify-content:flex-end;align-items:center;gap:.65rem;margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--module-border)}
.page-content .form-actions .btn{min-width:128px}
.page-content .document-items{display:flex;flex-direction:column;gap:.65rem;margin-top:.75rem}
.page-content .document-item{padding:.85rem;border:1px solid var(--module-border);border-radius:.65rem;background:var(--module-soft)}
.page-content .search-results{z-index:40;max-height:280px;overflow:auto;border-radius:.5rem}
.page-content .detail-card{border-left:3px solid var(--bs-info,#0dcaf0)!important}
.page-content .detail-label{display:block;margin-bottom:.25rem;color:var(--module-muted);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.page-content .filter-header{align-items:flex-end;flex-wrap:wrap}
.page-content .filter-title{display:flex;align-items:center;gap:.45rem;min-width:max-content;padding-bottom:.55rem;color:var(--module-heading);font-weight:600}
.page-content .filter-title i{font-size:1.2rem;color:var(--bs-primary,#0d6efd)}
.page-content .module-list-card>.card-body{border-radius:0 0 .75rem .75rem}
.page-content .modal-content{border:0;border-radius:.8rem;box-shadow:0 18px 50px rgba(16,24,40,.2);overflow:hidden}
.page-content .modal-header{min-height:60px;padding:.9rem 1.15rem;background:var(--module-soft);border-bottom:1px solid var(--module-border)}
.page-content .modal-footer{gap:.5rem;padding:.85rem 1.15rem;background:var(--module-soft);border-top:1px solid var(--module-border)}

/* Formato único de tablas del sistema */
.page-content .table-responsive{width:100%;border:1px solid var(--module-border);border-radius:.65rem;background:var(--module-surface);overflow-x:auto;-webkit-overflow-scrolling:touch}
.page-content .card-body.p-0>.table-responsive{border:0;border-radius:0 0 .75rem .75rem}
.page-content table.table{width:100%;margin-bottom:0!important;vertical-align:middle;border-collapse:separate;border-spacing:0;font-size:.9rem}
.page-content table.table>thead>tr>th{background:#f3f6f9;color:#344054;font-weight:600;border-bottom:1px solid #d8e0e8;padding:.75rem;white-space:nowrap}
.page-content table.table>tbody>tr>td{padding:.75rem;border-bottom:1px solid #edf0f3;background:var(--module-surface)}
.page-content table.table>tbody>tr:last-child>td{border-bottom:0}
.page-content table.table>tbody>tr:hover>td{background:#f8fbff}
.page-content table.table .btn-sm{min-height:31px}
.page-content table.table td.text-nowrap{white-space:nowrap}

body.dark-mode .page-content{--module-border:#3a4047;--module-muted:#aab2bd;--module-soft:#22262b;--module-surface:#272b30;--module-heading:#eef1f4}
body.dark-mode .page-content form .form-label,body.dark-mode .page-content .filter-label{color:#c8ced6}
body.dark-mode .page-content table.table>thead>tr>th{background:#22262b;color:#dde1e7;border-bottom-color:#3a4047}
body.dark-mode .page-content table.table>tbody>tr>td{border-bottom-color:#343a40}
body.dark-mode .page-content table.table>tbody>tr:hover>td{background:#2d3238}
body.dark-mode .page-content .module-form-card>.card-header{background:linear-gradient(90deg,rgba(13,110,253,.15),transparent)}

@media(max-width:767.98px){
    .page-content .module-header{align-items:stretch;flex-direction:column}.page-content .module-counter{align-self:flex-start}
    .page-content .card-header{align-items:flex-start;flex-wrap:wrap}.page-content .form-actions{align-items:stretch;flex-direction:column-reverse}.page-content .form-actions .btn{width:100%}
    .page-content table.table{font-size:.82rem;min-width:720px}.page-content table.table>thead>tr>th,.page-content table.table>tbody>tr>td{padding:.6rem}.page-content .table-responsive{border-radius:.45rem}
    .page-content .filter-title{width:100%;padding-bottom:0}
}
</style>
