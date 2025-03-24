@extends('layouts.app')

@section('title', 'Página de Login')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center">Login</h3>
                    {{-- Formulário --}}
                    <form method="POST" action="/login">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Usuario:</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name') }}" placeholder="Digite seu ID" required>
                        </div>
                        <div class="mb-3">
                            <label for="cidade" class="form-label">Cidade:</label>
                            <input type="text" name="cidade" class="form-control @error('cidade') is-invalid @enderror" id="cidade" value="{{ old('cidade') }}" placeholder="Digite sua cidade" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha:</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Digite sua senha" required required>
                            @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="d-grid">

                            <button type="submit" class="btn btn-primary" id="entrar">Entrar</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection