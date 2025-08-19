{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/modal_style.css') }}?v={{ time() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    @if(isset($cliente))
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
                console.log('asdasdasdasd', index, 'ididididid', id)
            });

            function toggleCol(index, buttonId) {
                var column = table.column(index);
                var visible = !column.visible();
                column.visible(visible);
                table.columns.adjust().draw(false);

                var input = $('.filtros td').eq(index).find('input');
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


            // uploadForm.addEventListener('submit', function(e) {
            // e.preventDefault(); // evita reload
            // const formData = new FormData(uploadForm);
            // console.log('chegou aqui')
            // fetch(uploadForm.action, {
            // method: 'POST',
            // body: formData
            // })
            // .then(resp => resp.json())
            // .then(data => {
            // console.log("Resposta:", data);
            // if (data.success) {
            // alert("Arquivo enviado com sucesso!");
            // // fecha modal
            // const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
            // modal.hide();
            // }
            // })
            // .catch(err => console.error("Erro:", err));
            // });

            // fileInput.addEventListener('change', function() {
            // if (fileInput.files.length === 0) return;

            // console.log('fileinputlistener')
            // const formData = new FormData(uploadForm);
            // fetch("{{ route('uploadFile', $cliente->id) }}", {
            // method: 'POST',
            // body: formData,
            // headers: {
            // 'X-Requested-With': 'XMLHttpRequest',
            // 'X-CSRF-TOKEN': '{{ csrf_token() }}'
            // }
            // })
            // .then(response => response.json())
            // .then(res => {
            // if (res.success) {
            // // Mostra alerta com fade
            // uploadResult.innerHTML = `
            // <div id="alert" class="alert alert-success fade">Arquivo enviado com sucesso!</div>
            // `;
            // const alertBox = document.getElementById('alert');
            // // Dispara a transição
            // requestAnimationFrame(() => {
            // alertBox.classList.add('show');
            // });

            // // Esconde com transição depois de 5s
            // setTimeout(() => {
            // alertBox.classList.remove('show');
            // setTimeout(() => {
            // uploadResult.innerHTML = ''; // remove do DOM após fade-out
            // }, 500); // igual ao tempo da transição no CSS
            // }, 5000);

            // // Atualiza lista de arquivos
            // fetch("{{ route('showFile', $cliente->id) }}", {
            // headers: {
            // 'X-Requested-With': 'XMLHttpRequest'
            // }
            // })
            // .then(res => res.text())
            // .then(html => {
            // listaArquivos.innerHTML = html;
            // });

            // } else {
            // uploadResult.innerHTML = '<div class="alert alert-danger">' + res.message + '</div>';
            // }
            // })
            // .catch(() => {
            // uploadResult.innerHTML = '<div class="alert alert-danger">Erro ao enviar arquivo.</div>';
            // });
            // });

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
                                        }, 300);
                                    }, 2000);
                                }, 300); // espera o modal atualizar antes de mostrar o alert
                            } else {
                                alert("Erro ao excluir o arquivo.");
                            }
                        })
                        .catch(() => alert("Erro de conexão ao excluir o arquivo."));
                }
            });
        });

        // uploadBtn.addEventListener('show.bs.modal', function() {
        // console.log()
        // fetch(" {{ route('uploadFile', $cliente->id) }}", {
        // headers: {
        // 'X-Requested-With': 'XMLHttpRequest'
        // }
        // })
        // .then(resp => resp.json())
        // .then(data => {
        // if (data.success) {
        // console.log('success')
        // Remove modal antigo
        // const oldModal = document.getElementById('arquivosModal');
        // if (oldModal) oldModal.remove();

        // // Insere novo modal
        // document.body.insertAdjacentHTML('beforeend', data.modalArquivo);

        // const modalEl = document.getElementById('arquivosModal');
        // const modal = new bootstrap.Modal(modalEl);
        // modal.show();
        // console.log(modal)
        // // Listener para envio do form
        // const form = modalEl.querySelector('#upload-form');
        // form.addEventListener('submit', function(e) {
        // e.preventDefault();

        // const formData = new FormData(this);
        // fetch(url, {
        // method: 'POST',
        // body: formData,
        // headers: {
        // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        // }
        // })
        // .then(r => r.json())
        // .then(result => {
        // const uploadResult = modalEl.querySelector('#upload-result');
        // if (result.success) {
        // uploadResult.innerHTML = `<div class="alert alert-success">Arquivo enviado com sucesso!</div>`;
        // // Aqui você pode atualizar a lista de arquivos, se tiver
        // } else {
        // uploadResult.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        // }
        // })
        // .catch(err => console.error(err));
        // });
        // }
        // })
        // .catch(err => console.error(err));
        // });



        // Quando o modal abrir, busca os arquivos


        // Quando um arquivo for selecionado, envia automaticamente
    </script>
    @endif
</body>

</html>