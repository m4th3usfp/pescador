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
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/locale/pt-br.js"></script>


    @yield('scripts') <!-- mudei de stack() pra yield() -->
    @if(isset($cliente))
    <script>
        document.addEventListener('DOMContentLoaded', function() { /////// link inadimplete que exibe se tiver expiration_date vencido
            const link = document.getElementById('autorizacaoLink');
            const documentos = document.getElementById('documentosPescador');
            const alerta = document.getElementById('alertaInadimplente');
            const card = document.getElementById('cardInadimplente');

            if (link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); // evita que o link recarregue a página
                    link.style.display = 'none'; // esconde o link
                    documentos.classList.remove('d-none'); // mostra os documentos
                    documentos.classList.add('d-block');
                    alerta.style.display = 'none';
                    card.style.display = 'none';
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

        // apartir daqui e funcionamento de mandar arquivos, e exibir tabelinha de arquivos no cadstro
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
                if (e.target.classList.contains('ver-btn')) {

                    let fileId = e.target.getAttribute('data-id');

                    fetch(`/log/view-file/${fileId}`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            file_id: fileId
                        })
                    });
                }
            });


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