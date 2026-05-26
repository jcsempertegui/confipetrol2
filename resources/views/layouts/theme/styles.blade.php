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