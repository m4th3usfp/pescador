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

                @php
                $isExpired = false;

                if (!empty($log->properties['Vencimento'])) {
                try {
                $date = \Carbon\Carbon::parse($log->properties['Vencimento']);

                // Só marca como vencido se for realmente no passado
                $isExpired = $date->isPast();

                } catch (\Exception $e) {

                // Se a data for inválida, NÃO marca como vencido
                $isExpired = false;

                }
                }
                $novo = $log->properties['Novo'] ?? [];
                $antigo = $log->properties['Antigo'] ?? [];

                $fieldLabels = [

                'name' => 'Nome',
                'address' => 'Endereço',
                'house_number' => 'Numero',
                'neighborhood' => 'Bairro',
                'city' => 'Cidade',
                'state' => 'Estado',
                'zip_code' => 'CEP',
                'mobile_phone' => 'Celular',
                'phone' => 'Telefone',
                'secondary_phone' => 'Telefone_secundario',
                'tax_id' => 'CPF',
                'identity_card' => 'RG',
                'identity_card_issuer' => 'Emissor',
                'rgp' => 'RGP',
                'pis' => 'PIS',
                'cei' => 'CEI',
                'drivers_license' => 'CNH',
                'license_issue_date' => 'Data CNH',
                'email' => 'Email',
                'expiration_date' => 'Vencimento',
                'affiliation' => 'Filiação',
                'birth_date' => 'Data nascimento',
                'birth_place' => 'Local nascimento',
                'notes' => 'Senha',
                'identity_card_issue_date' => 'Data RG',
                'father_name' => 'Nome pai',
                'mother_name' => 'Nome mãe',
                'rgp_issue_date' => 'Data RGP',
                'voter_id' => 'Carteira eleitor',
                'work_card' => 'Carteira trabalho',
                'profession' => 'Profissão',
                'marital_status' => 'Estado civil',

                ];

                $formatadoFinal = []; 

                foreach ($fieldLabels as $campo => $label) {

                $valorNovo = $novo[$campo] ?? null;
                $valorAntigo = $antigo[$campo] ?? null;

                if ($valorNovo !== null || $valorAntigo !== null) {

                $valorNovo = formatIfDateValue($valorNovo);
                $valorAntigo = formatIfDateValue($valorAntigo);

                $formatadoFinal[] = "
                <div class='alteracao-box'>
                    <div class='alteracao-titulo'>{$label}:</div>
                    <div class='alteracao-novo nowrap'><strong>Novo:</strong> {$valorNovo}</div>
                    <div class='alteracao-antigo nowrap'><strong>Antigo:</strong> {$valorAntigo}</div>
                </div>
                ";
                }
                };
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->properties['Usuario'] ?? '----' }}</td>
                    <td>{{ $log->log_name }}</td>
                    <td>
                        {{ $log->properties['Pescador_nome'] ?? '----' }}
                    </td>
                    <td>{{ $log->description ?? '----' }}</td>
                    <td style="color: {{ $isExpired ? '#C00000' : 'black' }}">
                        <div class="alteracoes-grid">
                        {!! implode('', $formatadoFinal) !!}                        

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