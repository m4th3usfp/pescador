@extends('layouts.app')

@section('title', 'listagem')

@section('content')
<div class="container mt-5">
    <!-- Cabeçalho com título e botão "Cadastrar Usuário" -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Lista de pescadores</h1>
        <a href="{{ route('Cadastro') }}" class="btn btn-primary">
            Cadastrar Pescador
        </a>
    </div>

    <!-- Exibe a cidade do usuário logado -->
    <p class="lead">Cidade: {{ $clientes->first()->cidade ?? 'Nenhum usuário encontrado' }}</p>

    <!-- Tabela para listar os usuários -->
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cidade</th>
                <th>Acesso</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
            <tr>
                <td>{{ $cliente->id }}</td>
                <td>{{ $cliente->name }}</td>
                <td>{{ $cliente->city }}</td>
                <td>{{ $cliente->city_id }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Nenhum usuário encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection