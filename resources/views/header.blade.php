<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Flexaust - Distributor Syndication</title>

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

        <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    </head>
    <body class="font-sans antialiased">
        <header class="app-header pt-2">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="logo text-center">
                            <a href="/"><img src="{{ asset('assets/images/logo.png') }}" alt="Flexaust"></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-blue pt-3 pb-2">
                <div class="container">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="logo">
                                <a href="/"><img class="w-100" src="{{ asset('assets/images/PP-XD-Header-Logo-Text.png') }}" alt="Flexaust"></a>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <nav class="navbar navbar-expand-lg navbar-light">
                                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
                                    <span class="navbar-toggler-icon"></span>
                                </button>
                                <div class="collapse navbar-collapse" id="navbarText">
                                    <ul class="navbar-nav ml-auto">
                                    @if (Auth::guest())
                                        <li class="nav-item">
                                            <a class="nav-link" href="/login">Login</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="/register">Register</a>
                                        </li>
                                    @else
                                        <li class="nav-item">
                                            <a class="nav-link" href="/dashboard">Dashboard</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="/logout">Logout</a>
                                        </li>
                                    @endif
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </header>