{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/modal_style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('styles')
</head>

<body>
    <main>
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>


    @stack('scripts')
    @if(isset($cliente))
    <script>
        const colunas = {
            2: '#Cidade',
            3: '#Acesso',
            4: '#Endereco',
            5: '#Telefone',
            6: '#Celular'
        };

        document.addEventListener('DOMContentLoaded', function() {  /////// link inadimplete que exibe se tiver expiration_date vencido
            const link = document.getElementById('autorizacaoLink');
            const documentos = document.getElementById('documentosPescador');
            const alerta = document.getElementById('alertaInadimplente');

            if (link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); // evita que o link recarregue a página
                    link.style.display = 'none'; // esconde o link
                    documentos.classList.remove('d-none'); // mostra os documentos
                    documentos.classList.add('d-block');
                    alerta.style.display = 'none';
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() { /////////// codigo do botao de receber anuidade 
            const form = document.getElementById('formAnuidade');
            if (form) {
                form.addEventListener('submit', function() {
                    // Aguarda um pouco o download começar, depois recarrega a página
                    setTimeout(() => {
                        location.reload();
                    }, 3000); // 3 segundos após clicar (ajuste se quiser)
                });
            }
        });

        $(document).ready(function() {
            var table = $('#tabelaPescadores').DataTable({
                responsive: true,
                dom: 't',
                pageLength: -1, // -1 significa "mostrar todas"
                lengthChange: false,
                order: [
                    [0, 'asc']
                ],
                ordering: true,
                initComplete: function(settings, json) {
                    // Perform actions after data is fully loaded and table is drawn
                    console.log('DataTables initComplete event fired!', json);
                    // here it freezes for a bit
                    $('.loading').hide();
                    $('#tabelaPescadores').show();
                }
            });

            function atualizarCores() {
                table.rows().every(function() {
                    var data = this.data();

                    // Pega o texto da coluna de vencimento (índice 7)
                    var textoData = data[7] ? data[7].trim() : '';

                    var celulaNome = $(this.node()).find('td').eq(1);
                    var linkNome = celulaNome.find('a');
                    
                    if (!textoData) {
                        // Se não existir data, deixa amarelo
                        linkNome.css('color', '#ce951c');
                        return; // sai do loop atual
                    }
                    // console.log(textoData)
                    var partes = textoData.split('/');
                    var dataVencimento = new Date(`${partes[2]}-${partes[1]}-${partes[0]}`);

                    var hoje = new Date();
                    hoje.setHours(0, 0, 0, 0);

                    if (!isNaN(dataVencimento)) {
                        if (dataVencimento >= hoje) {
                            linkNome.css('color', '#093270'); // azul
                        } else {
                            linkNome.css('color', '#b02a37'); // vermelho
                        }
                    } else {
                        // caso a data esteja num formato inválido
                        linkNome.css('color', '#ce951c'); // amarelo
                    }
                });
            }


            table.on('draw', function() {
                atualizarCores();
            });

            table.draw();

            document.addEventListener('keydown', function(e) {
                // Verifica se Ctrl (ou Cmd no Mac) + F foi pressionado
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
                    e.preventDefault(); // impede a busca padrão do navegador

                    // Seleciona o input desejado (ex: o input de nome da tabela)
                    const inputNome = document.querySelector('input[name="inputName"]'); // ajuste conforme o name do input
                    if (inputNome) {
                        inputNome.focus();
                        inputNome.select(); // opcional: seleciona o texto existente
                    }
                }
            });


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
                $(id).removeClass('btn-outline-primary').addClass('btn-outline-danger');
            });

            function toggleCol(index, buttonId) {
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

        document.addEventListener('DOMContentLoaded', function() {
            const sendbtn = document.getElementById('sendbtn');
            const fileInput = document.getElementById('fileInput');
            const uploadForm = document.getElementById('upload-form');
            const uploadModal = document.getElementById('uploadModal');
            const uploadResult = document.getElementById('upload-result');
            const arquivosModal = document.getElementById('arquivosModal');
            const listaArquivos = document.getElementById('listaArquivos');
            const deleteResult = document.getElementById('delete-result');

            // Clique no botão dispara input de arquivo

            console.log("DOM carregado");

            if (arquivosModal) {
                arquivosModal.addEventListener('show.bs.modal', () => {
                    fetch("{{ route('showFile', $cliente->id) }}", {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.text())
                        .then(html => {
    
                            listaArquivos.innerHTML = html;
    
                        })
                        .catch(() => {
                            listaArquivos.innerHTML = '<div class="alert alert-danger">Erro ao carregar arquivos.</div>';
                        });
                });
                setTimeout(() => {
                    const alert = document.querySelector('#alert'); // pega só 1
                    if (alert) {
                        alert.classList.add('show');
    
                        setTimeout(() => {
                            alert.classList.remove('show');
                            alert.remove(); // remove depois que some
                        }, 500); // tempo do fadeout (ajusta p/ bater com CSS do Bootstrap)
                    }
                }, 4000); // 4 segundos 
            }

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-btn')) {
                    let fileId = e.target.getAttribute('data-id');

                    if (!confirm("Tem certeza que deseja excluir este arquivo?")) return;

                    fetch(`/arquivos/${fileId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => {
                            if (res.ok) {
                                // Atualiza a lista de arquivos no modal
                                document.getElementById('arquivosModal')
                                    .dispatchEvent(new Event('show.bs.modal'));

                                // Aguarda um curto tempo para garantir que o modal foi atualizado
                                setTimeout(() => {
                                    const deleteResult = document.getElementById('delete-result');
                                    deleteResult.innerHTML = `
    <div id="alert-delete" class="alert alert-success fade">Arquivo excluído com sucesso!</div>
    `;
                                    const alertBox = document.getElementById('alert-delete');

                                    // Fade-in
                                    requestAnimationFrame(() => {
                                        alertBox.classList.add('show');
                                    });

                                    // Fade-out
                                    setTimeout(() => {
                                        alertBox.classList.remove('show');
                                        setTimeout(() => {
                                            deleteResult.innerHTML = '';
                                        }, 400);
                                    }, 3000);
                                }, 300); // espera o modal atualizar antes de mostrar o alert
                            } else {
                                alert("Erro ao excluir o arquivo.");
                            }
                        })
                        .catch(() => alert("Erro de conexão ao excluir o arquivo."));
                }
            });
        });
    </script>
    @endif
</body>

</html>