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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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
.page-content .module-modal-shell{display:block;background:rgba(15,23,42,.62);backdrop-filter:blur(3px);z-index:1060}
.page-content .module-modal-shell .modal-dialog{padding:.75rem}
.page-content .module-modal-shell .modal-content{max-height:calc(100vh - 1.5rem)}
.page-content .module-modal-shell .modal-title{color:var(--module-heading);font-weight:700}
.page-content .detail-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
.page-content .detail-summary-item{min-height:72px;padding:.8rem;border:1px solid var(--module-border);border-radius:.6rem;background:var(--module-soft);overflow-wrap:anywhere}

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
    .page-content .detail-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.page-content .module-modal-shell .modal-dialog{padding:0}.page-content .module-modal-shell .modal-content{max-height:100vh;border-radius:0}
}
/* Refinamiento visual general */
body{font-family:"Inter",sans-serif;background:#f4f7fb;color:#344054}
.page-content{--module-heading:#172b4d;--module-primary:#1769e0;--module-radius:.85rem;padding-top:1.5rem}
.page-content .module-header{position:relative;align-items:center;padding:1.15rem 1.25rem;border:1px solid #dbe7f5;border-radius:var(--module-radius);background:linear-gradient(115deg,#fff 0%,#f6faff 72%,#eef5ff 100%);box-shadow:0 7px 22px rgba(31,54,78,.055);overflow:hidden}
.page-content .module-header:after{content:"";position:absolute;right:-34px;top:-48px;width:150px;height:150px;border-radius:50%;background:rgba(23,105,224,.055);pointer-events:none}
.page-content .module-header>div,.page-content .module-header>span,.page-content .module-header>button{position:relative;z-index:1}
.page-content .module-header h4{font-weight:700;letter-spacing:-.02em}
.page-content .module-header p{font-size:.88rem}
.page-content>.card,.page-content .row>.col-lg-7>.card,.page-content .row>.col-lg-5>.card,.page-content .module-form-card,.page-content .module-list-card,.page-content .module-filter-card{border-radius:var(--module-radius);box-shadow:0 5px 18px rgba(31,54,78,.05)}
.page-content .card-header{min-height:62px;padding:.9rem 1.1rem;border-radius:var(--module-radius) var(--module-radius) 0 0}
.page-content .module-filter-card>.card-header{background:linear-gradient(90deg,rgba(23,105,224,.065),var(--module-soft))}
.page-content .input-group-text{border-color:#d0d5dd;background:#f8fafc;color:#667085}
.page-content .form-text{color:#7d8998;font-size:.74rem}
.page-content .card-footer{padding:.8rem 1rem;background:var(--module-soft);border-top:1px solid var(--module-border);border-radius:0 0 var(--module-radius) var(--module-radius)}
.page-content .btn{display:inline-flex;align-items:center;justify-content:center;gap:.18rem;border-radius:.5rem;font-weight:500;transition:transform .15s ease,box-shadow .15s ease,background-color .15s ease}
.page-content .btn:not(.btn-link):hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(31,54,78,.11)}
.page-content .btn-primary{background:#1769e0;border-color:#1769e0}
.page-content .badge{padding:.38rem .58rem;border-radius:.42rem;font-weight:600;letter-spacing:.01em}
.page-content .alert{border:0;border-left:4px solid currentColor;border-radius:.6rem}
.page-content .pagination{margin-bottom:0}
.page-content .page-link{border-color:var(--module-border);color:var(--module-primary)}
.page-content .dropdown-menu{border-color:var(--module-border);border-radius:.65rem;box-shadow:0 12px 30px rgba(31,54,78,.14)}
.page-content .module-empty{padding:3rem 1rem;text-align:center;color:var(--module-muted)}
.page-content .module-empty i{display:block;margin-bottom:.5rem;color:#a9b6c6;font-size:2.25rem}

/* Indicadores compactos compartidos */
.page-content .module-metrics-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin-bottom:1.25rem}
.page-content .module-metrics-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.page-content .module-metric-card{position:relative;display:flex;align-items:center;gap:.85rem;min-height:94px;padding:1rem;border:1px solid var(--module-border);border-radius:.75rem;background:var(--module-surface);box-shadow:0 4px 14px rgba(31,54,78,.045);overflow:hidden}
.page-content .module-metric-card:after{content:"";position:absolute;right:-24px;bottom:-35px;width:84px;height:84px;border-radius:50%;background:currentColor;opacity:.045}
.page-content .module-metric-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:.7rem;background:#edf4ff;color:#1769e0;font-size:1.4rem}
.page-content .module-metric-label{margin-bottom:.16rem;color:var(--module-muted);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.035em}
.page-content .module-metric-value{color:var(--module-heading);font-size:1.35rem;font-weight:700;line-height:1.15}
.page-content .module-metric-card.tone-success .module-metric-icon{background:#e9f8f0;color:#198754}
.page-content .module-metric-card.tone-danger .module-metric-icon{background:#fff0f1;color:#dc3545}
.page-content .module-metric-card.tone-warning .module-metric-icon{background:#fff7e5;color:#b7791f}
.page-content .module-metric-card.tone-info .module-metric-icon{background:#e8f8fc;color:#0b8fab}

/* Tarjetas equivalentes a filas para telefonos y tabletas */
.page-content .module-data-list{display:flex;flex-direction:column;gap:.65rem;padding:.65rem}
.page-content .module-data-card{padding:1rem;border:1px solid var(--module-border);border-radius:.7rem;background:var(--module-surface);box-shadow:0 2px 8px rgba(31,54,78,.035)}
.page-content .module-data-card:hover{border-color:#bdd1e8}
.page-content .module-data-card .module-data-actions{display:flex;gap:.5rem;margin-top:.85rem;padding-top:.75rem;border-top:1px solid var(--module-border)}

/* Columna de acciones siempre visible al desplazar tablas anchas */
.page-content table.table.table-with-actions>thead>tr>th:last-child,.page-content table.table.table-with-actions>tbody>tr>td:last-child{position:sticky;right:0;z-index:2;box-shadow:-8px 0 12px -12px rgba(16,24,40,.4)}
.page-content table.table.table-with-actions>thead>tr>th:last-child{z-index:3;background:#f3f6f9}
.page-content table.table.table-with-actions>tbody>tr>td:last-child{background:var(--module-surface)}
.page-content table.table.table-with-actions>tbody>tr:hover>td:last-child{background:#f8fbff}

/* Navegacion principal */
.sidebar-wrapper{box-shadow:5px 0 24px rgba(31,54,78,.07)}
.sidebar-wrapper .sidebar-header{border-bottom:1px solid #edf0f4}
.sidebar-wrapper .logo-text{font-weight:700;letter-spacing:.025em}
.sidebar-wrapper .metismenu .menu-label{margin-top:.65rem;color:#98a2b3;font-size:.66rem;font-weight:700;letter-spacing:.09em}
.sidebar-wrapper .metismenu a{margin:2px 9px;border-radius:.55rem;transition:background-color .16s ease,color .16s ease,transform .16s ease}
.sidebar-wrapper .metismenu a:hover{transform:translateX(2px)}
.sidebar-wrapper .metismenu .mm-active>a{background:linear-gradient(90deg,#1769e0,#3d86ea);color:#fff;box-shadow:0 5px 13px rgba(23,105,224,.2)}
.topbar{border-bottom:1px solid #e9eef5;box-shadow:0 3px 14px rgba(31,54,78,.045)}
.topbar .user-box{padding:.22rem .35rem;border-radius:.65rem}
.topbar .user-box:hover{background:#f5f8fc}

body.dark-mode{background:#1e2227;color:#d9dee5}
body.dark-mode .page-content .module-header{border-color:#3a4047;background:linear-gradient(115deg,#272b30,#22272d)}
body.dark-mode .page-content .input-group-text{border-color:#4b525a;background:#22262b;color:#bec5cf}
body.dark-mode .page-content .module-data-card,body.dark-mode .page-content .module-metric-card{background:#272b30}
body.dark-mode .page-content table.table.table-with-actions>thead>tr>th:last-child{background:#22262b}
body.dark-mode .page-content table.table.table-with-actions>tbody>tr>td:last-child{background:#272b30}
body.dark-mode .page-content table.table.table-with-actions>tbody>tr:hover>td:last-child{background:#2d3238}

@media(max-width:767.98px){
    .page-content{padding-top:1rem}.page-content .module-header{padding:1rem}.page-content .module-header>div:last-child{width:100%}.page-content .module-header>div:last-child>.btn{flex:1}
    .page-content .module-metrics-grid,.page-content .module-metrics-grid-3{grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem}.page-content .module-metric-card{min-height:84px;padding:.8rem}.page-content .module-metric-icon{flex-basis:36px;width:36px;height:36px}.page-content .module-metric-value{font-size:1.1rem}
}
@media(max-width:420px){.page-content .module-metrics-grid,.page-content .module-metrics-grid-3,.page-content .detail-summary-grid{grid-template-columns:1fr}.page-content .module-header .btn{width:100%}}
</style>
