@extends('layouts.app')
@section('content')

@if(Auth::check() && (Auth::user()->name === 'Matheus' || Auth::user()->name === 'Dabiane'))
<div class="container-fluid mt-2">
    <div class="d-flex flex-column align-items-center">
        <h2>Registro de Pagamentos</h2>
        <a href="{{ route('listagem') }}" class="btn btn-outline-secondary mt-2 mb-4 no-print">
            Voltar à listagem
        </a>
    </div>
    <div class="container-fluid col-md-7 no-print">
        <form method="GET" action="{{ route('showPaymentView') }}" class="mb-3">
            <div class="container-fluid row d-flex justify-content-center">
                <div class="col-md-3">
                    <label for="data_inicial">Data inicial:</label>
                    <input type="date" id="data_inicial" name="data_inicial" value="{{ request('data_inicial', now()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="data_final">Data final:</label>
                    <input type="date" id="data_final" name="data_final" value="{{ request('data_final', now()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="cidade_id">Colônia:</label>
                    <select id="cidade_id" name="cidade_id" class="form-control" required>
                        <option value="">-- Selecione --</option>
                        @if(isset($cidadeUsuario))
                        @foreach ($cidadeUsuario as $cidade)
                        <option value="{{ $cidade->id }}" {{ request('cidade_id') == $cidade->id ? 'selected' : '' }}>
                            {{ $cidade->name }}
                        </option>
                        @endforeach
                        @endif
                    </select>

                </div>
            </div>
        </form>
    </div>
    <div class="container-fluid">
        @if (isset($registros) && $registros->count())
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Data</th>
                    <th>Pescador</th>
                    <th>Usuário</th>
                    <th>Venc. Antigo</th>
                    <th>Venc. Novo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registros as $r)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $r->fisher_name }}</td>
                    <td>{{ $r->user }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->old_payment)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->new_payment)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        @if(request()->has('cidade_id'))
        <div class="alert alert-warning mt-4">Nenhum registro encontrado no período informado.</div>
        @endif
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dataInicial = document.getElementById('data_inicial');
        const dataFinal = document.getElementById('data_final');
        const cidadeId = document.getElementById('cidade_id');
        const form = document.querySelector('form');

        function trySubmit() {
            // Só envia se todos os campos tiverem valor
            if (dataInicial.value && dataFinal.value && cidadeId.value) {
                form.submit();
            }
        }

        dataInicial.addEventListener('change', trySubmit);
        dataFinal.addEventListener('change', trySubmit);
        cidadeId.addEventListener('change', trySubmit);
    });
</script>
@endif
@endsection