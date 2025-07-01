{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('styles')
</head>

<body>
    <main>
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    @stack('scripts')
    <script>
        const colunas = {
            2: '#Cidade',
            3: '#Acesso',
            4: '#Endereco',
            5: '#Telefone',
            6: '#Celular'
        };

        $(document).ready(function() {
            var table = $('#tabelaPescadores').DataTable({
                responsive: true,
                dom: 't',
            });

            function atualizarCores() {
                table.rows().every(function() {
                    var data = this.data();

                    // Pega o texto da coluna de vencimento (índice 7)
                    var textoData = data[7].trim();

                    var partes = textoData.split('/');
                    var dataVencimento = new Date(`${partes[2]}-${partes[1]}-${partes[0]}`);

                    var hoje = new Date();
                    hoje.setHours(0, 0, 0, 0);

                    var celulaNome = $(this.node()).find('td').eq(1);
                    var linkNome = celulaNome.find('a');

                    if (!isNaN(dataVencimento)) {
                        if (dataVencimento >= hoje) {
                            linkNome.css('color', 'blue');
                        } else {
                            linkNome.css('color', 'red');
                        }
                    }
                });
            }

            table.on('draw', function() {
                atualizarCores();
            });

            table.draw();

            $('#tabelaPescadores thead tr.filtros th').each(function(i) {
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

            Object.entries(colunas).forEach(([index, id]) => {
                table.column(index).visible(false);
                $('.filtros').eq(index).find('input').hide();
                $(id).removeClass('btn-outline-secondary').addClass('btn-outline-danger');
            });

            function toggleCol(index, buttonId) {
                var column = table.column(index);
                var visible = !column.visible();
                column.visible(visible);
                table.columns.adjust().draw(false);

                var input = $('.filtros').eq(index).find('input');
                input.toggle(visible);

                var button = $('#' + buttonId);
                if (!visible) {
                    button.removeClass('btn-outline-secondary').addClass('btn-outline-danger');
                } else {
                    button.removeClass('btn-outline-danger').addClass('btn-outline-secondary');
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
            $('#Acesso').on('click', function() {
                toggleCol(3, 'Acesso');
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
</body>

</html>