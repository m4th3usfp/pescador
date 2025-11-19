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
            <button id="RGP" class="btn btn-outline-primary"><i class="bi bi-dash-circle me-1"></i>RGP</button>
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
                        <th><input type="text" placeholder="RGP" /></th>
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
                        <th>RGP</th>
                        <th>Endereço</th>
                        <th>Telefone</th>
                        <th>Celular</th>
                        <th>Vencimento</th>
                        <th>Nascimento</th>
                        <th class="no-print" style="width: 60px">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodylistagem">
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    console.log("SCRIPT RODANDO");

    var data = {!! json_encode($clientes) !!};
    console.log(data);

    dayjs.locale('pt-br');

    $(document).ready(function () {
        console.log("READY");

        const colunas = {
            2: '#Cidade',
            3: '#RGP',
            4: '#Endereco',
            5: '#Telefone',
            6: '#Celular'
        };

       var table = $('#tabelaPescadores').DataTable({
            responsive: true,
            dom: 't',
            data: data,
            columns: [
                { data: 'record_number' },
                { data: 'name',
                    render: function (data, type, row) {
                        let nome = '';
                        linkNome = `<a href="/listagem/${row.id}">${data}</a>`;
                        const exp = row.expiration_date;

                        if (!exp) {

                            color = "color: #856404;";

                        } else {

                            const hoje = dayjs();

                            const dataExp = dayjs(exp);

                            if (dataExp.isBefore(hoje, "day")) {

                                color = "color: #721c24;";

                            } else {
                                
                                color = "color: #084298;";
                            }
                        }
                        return `<a href="/listagem/${row.id}" style="${color}">${data}</a>`;
                    } },
                                
                { data: 'city' },
                { data: 'rgp' },
                { data: 'address' },
                { data: 'phone' },
                { data: 'mobile_phone' },
                { data: 'expiration_date',
                    render: function (data) {
                    if (!data) return '';
                    return data ? dayjs(data).format('DD/MM/YYYY') : '';

                } },
                { data: 'birth_date',
                    render: function (data) {
                    if (!data) return '';
                    return data ? dayjs(data).format('DD/MM/YYYY') : '';

                } },
                { 
                data: null, // Não usa uma propriedade específica dos dados
                render: function (data, type, row) {
                    // Cria os botões de ação dinamicamente
                    return `
                        <div class="d-flex no-print w-25">
                            <a href="/listagem/${row.id}" class="btn btn-success btn-sm me-2 no-print">Editar</a>
                            <form action="/listagem/${row.id}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este pescador? ${row.name}');">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-danger btn-sm no-print">Excluir</button>
                            </form>
                        </div>
                    `;
                },
            orderable: false, // Impede ordenação nesta coluna
            searchable: false // Impede busca nesta coluna
        }
            ],
            pageLength: -1, // -1 = mostrar todos
            lengthChange: false,
            order: [[0, 'asc']], // ordena pelo nome
            ordering: true,
            deferRender: true,
            columnDefs: [
                { width: "50px", targets: 0 },
                { width: "370px", targets: 1 },
                { width: "5px", targets: 3 },
            ],


            initComplete: function(settings, json) {
                console.log('DataTables initComplete event fired!', json);
                
                // quando terminar de carregar, esconde o loading e mostra a tabela
                $('.loading').hide();
                $('#tabelaPescadores').show();
            }
        });

        $('#tabelaPescadores thead tr.filtros th').each(function(i) { // cada tecla pressionada dentro do campo de texto do filtro vai fazendo uma nova busca
                $('input', this).css({

                    'width': '100px',
                    'font-size': '13px'

                }).on('keyup change', function() {

                    if (table.column(i).search() !== this.value) {

                        table
                            .column(i)
                            .search(this.value)
                            .draw();
                    }

                });

            });

            Object.entries(colunas).forEach(([index, id]) => { // metodo para tabela terminar de carregar ja com as coluans ocultas e exibidas configuradas
                table.column(index).visible(false);
                $('.filtros').eq(index).find('input').hide();
                $(id).removeClass('btn-outline-primary').addClass('btn-outline-danger');
            });

            function toggleCol(index, buttonId) { // oculta e exibe a coluna da tabela clicada junto com campo de texto, e muda a cor do botao e o icone;
                var column = table.column(index);
                var visible = !column.visible();
                // var icon = $('#', iconId);
                // console.log(column)
                column.visible(visible);
                table.columns.adjust().draw(false);

                var input = $('.filtros td').eq(index).find('input');
                input.toggle(visible);

                var button = $('#' + buttonId);
                if (!visible) {
                    button.removeClass('btn-outline-primary').addClass('btn-outline-danger')
                        .find('i').removeClass('bi-plus-circle').addClass('bi-dash-circle');

                } else {
                    button.removeClass('btn-outline-danger').addClass('btn-outline-primary')
                        .find('i').removeClass('bi-dash-circle').addClass('bi-plus-circle');
                }
            }

            $('#Ficha').on('click', function() {
                toggleCol(0, 'Ficha');
            });
            $('#Nome').on('click', function() {
                toggleCol(1, 'Nome');
            });
            $('#Cidade').on('click', function() {
                toggleCol(2, 'Cidade');
            });
            $('#RGP').on('click', function() {
                toggleCol(3, 'RGP');
            });
            $('#Endereco').on('click', function() {
                toggleCol(4, 'Endereco');
            });
            $('#Telefone').on('click', function() {
                toggleCol(5, 'Telefone');
            });
            $('#Celular').on('click', function() {
                toggleCol(6, 'Celular');
            });
            $('#Vencimento').on('click', function() {
                toggleCol(7, 'Vencimento');
            });
            $('#Nascimento').on('click', function() {
                toggleCol(8, 'Nascimento');
            });

    });
</script>
@endsection

                        