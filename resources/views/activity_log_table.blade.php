@extends('layouts.app')
@section('content')

@if(Auth::check() && (Auth::user()->name === 'Matheus' || Auth::user()->name === 'Dabiane'))
<div class="container-fluid mt-2">
    <div class="d-flex flex-column align-items-center">
        <h2>Registro de Atividades</h2>
        <a href="{{ route('listagem') }}" class="btn btn-outline-secondary mt-2 mb-4 me-4 no-print">
            Voltar à listagem
        </a>
    </div>
    <div class="container-fluid">
        @if (isset($logs) && $logs->count() && isset($user))
        <table class="table table-hover" id="log_table">
            <thead>
                <tr>
                    <th>Criado_em</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Pescador</th>
                    <th>Descrição</th>
                    <th>Alterações</th>
                </tr>
            </thead>
            <tbody>
                @php
                function isDateValue($value) {
                if (empty($value)) {
                return false;
                }

                try {
                return \Carbon\Carbon::hasFormat($value, 'Y-m-d')
                || \Carbon\Carbon::hasFormat($value, 'd/m/Y')
                || strtotime($value) !== false;
                } catch (\Exception $e) {
                return false;
                }
                }

                function formatIfDateValue($value) {
                if (empty($value)) {
                return $value;
                }

                return isDateValue($value)
                ? \Carbon\Carbon::parse($value)->format('d/m/Y')
                : $value;
                }
                @endphp


                @foreach ($logs as $log)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->properties['Usuario'] ?? '----' }}</td>
                    <td>{{ $log->log_name }}</td>
                    <td>
                        {{ $log->properties['Pescador_nome'] ?? '----' }}
                    </td>
                    <td>{{ $log->description ?? '----' }}</td>
                    <td>
                        <div class="alteracoes-grid">
                            
                        </div>
                    </td>
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
@endif
@endsection