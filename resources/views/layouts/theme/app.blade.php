<!doctype html>
<html lang="es" translate="no">

<head>

    @include('layouts.theme.styles')


    <title>Mastec Pos - @stack('title', 'Home')</title>
    <meta name="google" content="notranslate"> <!-- Agregar esta línea -->
    <meta http-equiv="Content-Language" content="es"> <!-- Agregar esta línea -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>
@auth

<body>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark-mode') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        @include('layouts.theme.sidebar')
        <!--end sidebar wrapper -->
        <!--start header -->

        @include('layouts.theme.header')

        <!--end header -->
        <!--start page wrapper -->
        <div class="page-wrapper">
            @yield('content')
        </div>
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->

    </div>
    <!--end wrapper-->

    @include('layouts.theme.scripts')

</body>
@endauth
@guest
@include('pages.401')
@endguest

</html>