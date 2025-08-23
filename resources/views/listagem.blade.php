@extends('layouts.app')

@section('title', 'listagem')

@section('content')
<div class="container-fluid mt-5">
    <!-- Cabeçalho com título e botão "Cadastrar Usuário" -->
    <div class="container shadow rounded-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <h1>Lista de pescadores</h1>
            <div class="d-flex flex-column align-items-end mt-2">
                @if(Auth::check() && Auth::user()->name === 'Matheus')
                <a href="{{ route('showPaymentView') }}" class="btn btn-success mb-2">
                    Registro de Pagamentos
                </a>
                @endif
                <a href="{{ route('Cadastro') }}" class="btn btn-primary">
                    Cadastrar Pescador
                </a>
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>

        <!-- Exibe a cidade do usuário logado -->
        <p class="lead">Cidade: {{ Auth::user()->city ?? 'Nenhum usuário logado' }}</p>
        <!-- Tabela para listar os usuários -->
        <div class="mb-3">
            <button id="Ficha" class="btn btn-outline-secondary">Ficha</button>
            <button id="Nome" class="btn btn-outline-secondary">Nome</button>
            <button id="Cidade" class="btn btn-outline-secondary">Cidade</button>
            <button id="Acesso" class="btn btn-outline-secondary">Acesso</button>
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
                        <th><input type="text" placeholder="Ficha" /></th>
                        <th><input type="text" placeholder="Nome" /></th>
                        <th><input type="text" placeholder="Cidade" /></th>
                        <th><input type="text" placeholder="Acesso" /></th>
                        <th><input type="text" placeholder="Endereço" /></th>
                        <th><input type="text" placeholder="Telefone" /></th>
                        <th><input type="text" placeholder="Celular" /></th>
                        <th><input type="text" placeholder="Vencimento" /></th>
                        <th><input type="text" placeholder="Nascimento" /></th>
                    </tr>
                    <tr>
                        <th>Ficha</th>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>Acesso</th>
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
                        <td class="text-nowrap">{{ $cliente->record_number}}</td>
                        <td class="text-nowrap"><a href="{{route('pescadores.edit', $cliente->id)}}">{{ $cliente->name }}</a></td>
                        <td class="text-nowrap">{{ $cliente->city }}</td>
                        <td class="text-nowrap">{{ $cliente->city_id }}</td>
                        <td class="text-nowrap">{{ $cliente->address}}</td>
                        <td class="text-nowrap">{{ $cliente->phone}}</td>
                        <td class="text-nowrap">{{ $cliente->mobile_phone}}</td>
                        <td class="text-nowrap">
                            {{ $cliente->expiration_date ? \Carbon\Carbon::parse($cliente->expiration_date)->format('d/m/Y') : '' }}
                        </td>
                        <td class="text-nowrap">{{ $cliente->birth_date ? \Carbon\Carbon::parse($cliente->expiration_date)->format('d/m/Y') : '' }}</td>
                        <td class="d-flex">
                            <a href="{{ route('pescadores.edit', $cliente->id) }}" class="btn btn-success btn-sm me-2">Editar</a>
                            <form action="{{ route('pescadores.destroy', $cliente->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este pescador? {{ $cliente->name }}');">
                                @csrf
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
</div>
@endsection