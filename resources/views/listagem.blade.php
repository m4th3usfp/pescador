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
    <p class="lead">Cidade: {{ Auth::user()->city ?? 'Nenhum usuário logado' }}</p>

    <!-- Tabela para listar os usuários -->
    <div class="mb-3">
        <button id="Nome" class="btn btn-outline-secondary">Nome</button>
        <button id="Cidade" class="btn btn-outline-secondary">Cidade</button>
        <button id="Acesso" class="btn btn-outline-secondary">Acesso</button>
        <button id="Ficha" class="btn btn-outline-secondary">Ficha</button>
        <button id="Endereco" class="btn btn-outline-secondary">Endereço</button>
        <button id="Telefone" class="btn btn-outline-secondary">Telefone</button>
        <button id="Celular" class="btn btn-outline-secondary">Celular</button>
        <button id="Vencimento" class="btn btn-outline-secondary">Vencimento</button>
        <button id="Nascimento" class="btn btn-outline-secondary">Nascimento</button>
    </div>
    <div class="table-responsive">
        <table class="datatable table table-striped w-100" id="tabelaPescadores">

            <thead class="thead-dark">
                <tr class="filtros" id="">
                    <th><input type="text" placeholder="ID" /></th>
                    <th><input type="text" placeholder="Nome" /></th>
                    <th><input type="text" placeholder="Cidade" /></th>
                    <th><input type="text" placeholder="Acesso" /></th>
                    <th><input type="text" placeholder="Ficha" /></th>
                    <th><input type="text" placeholder="Endereço" /></th>
                    <th><input type="text" placeholder="Telefone" /></th>
                    <th><input type="text" placeholder="Celular" /></th>
                    <th><input type="text" placeholder="Vencimento" /></th>
                    <th><input type="text" placeholder="Nascimento" /></th>
                </tr>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Cidade</th>
                    <th>Acesso</th>
                    <th>Ficha</th>
                    <th>Endereço</th>
                    <th>Telefone</th>
                    <th>Celular</th>
                    <th>Vencimento</th>
                    <th>Nascimento</th>
                    <th style="width: 60px">Ações</th>
                </tr>
            </thead>
            <tbody class="">
                @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->id }}</td>
                    <td class="text-nowrap">{{ $cliente->name }}</td>
                    <td class="text-nowrap">{{ $cliente->city }}</td>
                    <td class="text-nowrap">{{ $cliente->city_id }}</td>
                    <td class="text-nowrap">{{ $cliente->record_number}}</td>
                    <td class="text-nowrap">{{ $cliente->address}}</td>
                    <td class="text-nowrap">{{ $cliente->phone}}</td>
                    <td class="text-nowrap">{{ $cliente->mobile_phone}}</td>
                    <td class="text-nowrap">{{ $cliente->expiration_date}}</td>
                    <td class="text-nowrap">{{ $cliente->birth_date}}</td>
                    <td class="d-flex">
                        @csrf
                        <a href="{{ route('pescadores.edit', $cliente->id) }}" class="btn btn-success btn-sm me-2">Editar</a>
                        <form action="{{ route('pescadores.destroy', $cliente->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este pescador? {{ $cliente->name }}');">
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">Nenhum usuário encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection