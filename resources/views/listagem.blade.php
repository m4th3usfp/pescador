@extends('layouts.app')

@section('title', 'listagem')

@section('content')
<div class="container-fluid mt-5">
    <div class="container shadow rounded-4">

        <div class="d-flex justify-content-between align-items-start mb-4">
            {{-- Coluna da esquerda --}}
            <div>
                <!-- <h1>Lista de pescadores</h1> -->
                <h3>Olá {{ Auth::user()->name }}</h3>
                {{-- Logout --}}
                <form action="{{ route('logout') }}" method="POST" class="mb-2 no-print">
                    @csrf
                    <button type="submit" class="btn btn-danger">Sair</button>
                </form>
            </div>

            {{-- Coluna da direita --}}
            <div class="d-flex flex-column align-items-end mt-2">
                @if(Auth::check() && (Auth::user()->name === 'Matheus' || Auth::user()->name === 'Dabiane'))
                <a href="{{ route('showPaymentView') }}" class="btn btn-success mb-2 no-print">
                    Registro de Pagamentos
                </a>
                @endif
                <a href="{{ route('Cadastro') }}" class="btn btn-primary no-print">
                    Cadastrar Pescador
                </a>
            </div>
        </div>
        
            {{-- Select logo abaixo do Olá --}}
            @if(Auth::check() && (Auth::user()->name === 'Matheus' || Auth::user()->name === 'Dabiane'))
            <div class="d-flex justify-content-between align-items-start">
                <form method="GET" action="{{ route('listagem') }}" class="mt-2 no-print">
                    <label for="city" class="form-label">Selecionar cidade:</label>
                    <select name="city" id="city" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($allowedCities as $city)
                        <option value="{{ $city }}" @if($city==$cityName) selected @endif>
                            {{ $city }}
                        </option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif
            
            {{-- Cidade selecionada --}}
            @if(Auth::check() && (Auth::user()->name === 'Matheus' || Auth::user()->name === 'Dabiane') && $cityName)
            <p class="lead">Cidade selecionada: <strong>{{ $cityName }}</strong></p>
            @endif
        
        <h2>Lista de pescadores</h2>

        {{-- Resto da página: botões de filtro + tabela --}}
        <div class="mb-3 no-print">
            <button id="Ficha" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Ficha</button>
            <button id="Nome" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Nome</button>
            <button id="Cidade" class="btn btn-outline-primary"><i class="bi bi-dash-circle me-1"></i>Cidade</button>
            <button id="Acesso" class="btn btn-outline-primary"><i class="bi bi-dash-circle me-1"></i>Acesso</button>
            <button id="Endereco" class="btn btn-outline-primary"><i class="bi bi-dash-circle me-1"></i>Endereço</button>
            <button id="Telefone" class="btn btn-outline-primary"><i class="bi bi-dash-circle me-1"></i>Telefone</button>
            <button id="Celular" class="btn btn-outline-primary"><i class="bi bi-dash-circle me-1"></i>Celular</button>
            <button id="Vencimento" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Vencimento</button>
            <button id="Nascimento" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i>Nascimento</button>
        </div>

        <div class="loading">Carregando...</div>
        <div class="table-responsive dataTables_wrapper">
            <table class="datatable table table-striped w-100" id="tabelaPescadores" style="display: none">
                <thead class="thead-dark">
                    <tr class="filtros no-print">
                        <th><input type="text" placeholder="Ficha" /></th>
                        <th><input type="text" name="inputName" placeholder="Nome" /></th>
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
                        <th class="no-print" style="width: 60px">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodylistagem">
                    @forelse ($clientes as $cliente)
                    <tr>
                        <td class="text-nowrap">{{ $cliente->record_number }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('pescadores.edit', $cliente->id) }}">{{ $cliente->name }}</a>
                        </td>
                        <td class="text-nowrap w-25">{{ $cliente->city }}</td>
                        <td class="text-nowrap w-25">{{ $cliente->city_id }}</td>
                        <td class="text-nowrap w-25">{{ $cliente->address }}</td>
                        <td class="text-nowrap w-25">{{ $cliente->phone }}</td>
                        <td class="text-nowrap w-25">{{ $cliente->mobile_phone }}</td>
                        <td class="text-nowrap w-25">
                            @if ($cliente->expiration_date && \Carbon\Carbon::hasFormat($cliente->expiration_date, 'Y-m-d'))
                            {{ \Carbon\Carbon::parse($cliente->expiration_date)->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="text-nowrap w-25">
                            @if ($cliente->birth_date && \Carbon\Carbon::hasFormat($cliente->birth_date, 'Y-m-d'))
                            {{ \Carbon\Carbon::parse($cliente->birth_date)->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="d-flex no-print w-25">
                            <a href="{{ route('pescadores.edit', $cliente->id) }}" class="btn btn-success btn-sm me-2 no-print">Editar</a>
                            <form action="{{ route('pescadores.destroy', $cliente->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este pescador? {{ $cliente->name }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm no-print">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">Nenhum usuário encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection