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
                scrollx: true
            });

            // Filtro por coluna
            $('#tabelaPescadores thead tr.filtros th').each(function(i) {
                $('input', this).css({
                    'width': '80px', // Define a largura dos inputs
                    'font-size': '12px' // Opcional: reduz o texto
                }).on('keyup change', function() {
                    if (table.column(i).search() !== this.value) {
                        table
                            .column(i)
                            .search(this.value)
                            .draw();
                    }
                });
            });

            // Exibir/ocultar colunas e filtros juntos
            function toggleCol(index) {
                var column = table.column(index);
                column.visible(!column.visible());

                // Mostrar/ocultar input relacionado
                var input = $('#tabelaPescadores thead tr.filtros th').eq(index).find('input');
                input.toggle(column.visible());
            }

            $('#Nome').on('click', function() {
                toggleCol(1);
            });
            $('#Cidade').on('click', function() {
                toggleCol(2);
            });
            $('#Acesso').on('click', function() {
                toggleCol(3);
            });
            $('#Ficha').on('click', function() {
                toggleCol(4);
            });
            $('#Endereco').on('click', function() {
                toggleCol(5);
            });
            $('#Telefone').on('click', function() {
                toggleCol(6);
            });
            $('#Celular').on('click', function() {
                toggleCol(7);
            });
            $('#Vencimento').on('click', function() {
                toggleCol(8);
            });
            $('#Nascimento').on('click', function() {
                toggleCol(9);
            });
        });
    </script>
</body>

</html>