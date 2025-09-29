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
                                <label for="name" class="form-label">Usuário:</label>
                                <input type="text" name="name" class="form-control" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Senha:</label>
                                <input type="password" name="password" class="form-control" id="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Entrar</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div>
    </div>
    @endsection