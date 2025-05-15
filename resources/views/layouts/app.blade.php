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
            $(document).ready(function() {
                var table = $('#tabelaPescadores').DataTable({
                    // autoWidth: false,
                    // scrollX: true,
                    responsive: true,
                    dom: 't',


                });

                const colunas = {
                    3: '#Acesso',
                    4: '#Ficha',
                    5: '#Endereco',
                    6: '#Telefone',
                    7: '#Celular',
                    8: '#Vencimento',
                    9: '#Nascimento',
                };

                $('#tabelaPescadores thead tr.filtros th').each(function(i) {
                    $('input', this).css({
                        'width': '100px', // Define a largura dos inputs
                        'font-size': '13px' // Opcional: reduz o texto
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

                // Filtro por coluna

                // Exibir/ocultar colunas e filtros juntos
                function toggleCol(index, buttonId) {
                    var column = table.column(index);
                    var visible = !column.visible();
                    column.visible(visible);

                    // Corrige largura e alinhamento
                    table.columns.adjust().draw(false);

                    // Mostrar/ocultar input relacionado
                    var input = $('.filtros').eq(index).find('input');
                    input.toggle(visible);

                    // Trocar a cor do botão
                    var button = $('#' + buttonId);
                    if (!visible) {
                        button.removeClass('btn-outline-secondary').addClass('btn-outline-danger');
                    } else {
                        button.removeClass('btn-outline-danger').addClass('btn-outline-secondary');
                    }
                }


                $('#Nome').on('click', function() {
                    toggleCol(1, 'Nome');
                });
                $('#Cidade').on('click', function() {
                    toggleCol(2, 'Cidade');
                });
                $('#Acesso').on('click', function() {
                    toggleCol(3, 'Acesso');
                });
                $('#Ficha').on('click', function() {
                    toggleCol(4, 'Ficha');
                });
                $('#Endereco').on('click', function() {
                    toggleCol(5, 'Endereco');
                });
                $('#Telefone').on('click', function() {
                    toggleCol(6, 'Telefone');
                });
                $('#Celular').on('click', function() {
                    toggleCol(7, 'Celular');
                });
                $('#Vencimento').on('click', function() {
                    toggleCol(8, 'Vencimento');
                });
                $('#Nascimento').on('click', function() {
                    toggleCol(9, 'Nascimento');
                });
            });
        </script>
</body>

</html>