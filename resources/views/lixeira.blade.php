@extends('layouts.app')
@section('content')

@can('manage-trash')
<div class="container-fluid mt-2">
    <div class="d-flex flex-column align-items-center">
        <h2>Lixeira</h2>
        <div class="d-flex flex-wrap gap-3 justify-content-center align-items-end">
            <a href="{{ route('listagem', ['city' => $cityName]) }}" class="btn btn-outline-secondary">
                Voltar à listagem
            </a>
            <form method="GET" action="{{ route('pescadores.trash') }}">
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
        <p class="lead mt-2">Cidade selecionada: <strong>{{ $cityName }}</strong></p>
        <form action="{{ route('pescadores.restoreAll') }}" method="POST" class="mt-3"
              onsubmit="return confirm('Tem certeza que deseja restaurar todos os pescadores desta cidade?');">
            @csrf
            <button type="submit" class="btn btn-success">Restaurar todos</button>
        </form>
    </div>
    <div class="container-fluid">
        @if (isset($trashed) && $trashed->count())
        <table class="table table-hover" id="lixeira_table">
            <thead>
                <tr>
                    <th>Ficha</th>
                    <th>Nome</th>
                    <th>Cidade</th>
                    <th>Excluído em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trashed as $fisherman)
                <tr>
                    <td>{{ $fisherman->record_number }}</td>
                    <td>{{ $fisherman->name }}</td>
                    <td>{{ $fisherman->city }}</td>
                    <td>{{ $fisherman->deleted_at ? \Carbon\Carbon::parse($fisherman->deleted_at)->format('d/m/Y H:i:s') : '----' }}</td>
                    <td>
                        <form action="{{ route('pescadores.restore', $fisherman->id) }}" method="POST"
                              onsubmit="return confirm('Restaurar o pescador {{ $fisherman->name }}?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Restaurar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-center">
            {{ $trashed->links() }}
        </div>
        @else
        <div class="alert alert-success mt-4">Nenhum pescador na lixeira da cidade {{ $cityName }}.</div>
        @endif
    </div>
</div>
@endcan
@endsection