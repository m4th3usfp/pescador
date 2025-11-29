@extends('layouts.app')
@section('title', 'Cadastrar Usuário')
@section('content')
<div class="container mt-4">
    <div class="row">
        <!-- Coluna Esquerda (Formulário existente) -->
        <div class="{{ isset($cliente) ? 'col-md-8 pe-4' : 'col-md-12' }}">
            <div class="p-4 border rounded shadow">
                <h2 class="mb-3">{{ isset($cliente) ? "Editar pescador: $cliente->name" : 'Cadastrar pescador' }}</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('listagem') }}" class="btn btn-outline-secondary">
                        Voltar à listagem
                    </a>
                    @if(isset($cliente))
                    @method('POST')
                    <form method="POST" id="formAnuidade" class="d-grid gap-2" action="{{ route('pescadores.receiveAnnual', $cliente->id) }}" style="display:inline;" onsubmit="return confirm('Receber deste pescador ? {{ $cliente->name }}');">
                        @csrf
                        <button type="submit" class="btn btn-info">Receber anuidade</button>
                    </form>
                    @endif
                </div>
                @if(isset($cliente))
                {{-- form escondido para upload --}}
                <form id="upload-form" method="POST" action="{{ route('uploadFile', $cliente->id) }}" enctype="multipart/form-data" style="display:none;">
                    @csrf
                    <input type="file" name="fileInput" id="fileInput" required />

                </form>

                @if(session('success'))
                <div class="alert alert-success" id="alert">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <div id="upload-result"></div>

                {{-- Modal de exibição de arquivos --}}
                <div class="modal fade" id="arquivosModal" tabindex="-1" aria-labelledby="arquivosModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Arquivos de {{ $cliente->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <div id="listaArquivos">
                                    {{-- conteúdo carregado via AJAX --}}
                                    <div class="text-center py-4">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">

                    <div class="modal-dialog" id="modal-dialog">

                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">Upload de arquivos</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>

                            <form id="upload-form" action="{{ route('uploadFile', $cliente->id) }}" method="POST" enctype="multipart/form-data">

                                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                <div class="modal-body">

                                    <h6 class="mb-3">Escolha o arquivo:</h6>

                                    <div class="mb-2">

                                        <input type="file" id="fileInput" name="fileInput" class="form-control" required>

                                    </div>

                                    <label for="description" class="form-label mb-1">Nome do arquivo:</label>
                                    <input type="text" id="description" name="description" class="form-control" />
                                    <!-- <div id="upload-result" class="mt-3"></div>
                                    <div id="listaArquivos" class="mt-2"></div> -->
                                </div>

                                <div class="modal-footer">

                                    <button type="submit" id="sendbtn" class="btn btn-success">Enviar</button>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @endif
                <form class="" method="POST" action="{{ isset($cliente) ? route('pescadores.update', $cliente->id) : route('store') }}">
                    <div class="container col-12 mt-4">
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="record_number" class="form-label mb-1 me-1">Ficha:</label>
                            <input type="text" class="form-control borda-grossa" id="record_number" name="record_number" value="{{ $recordNumber }}" readonly>
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="name" class="form-label mb-1 me-1">nome:</label>
                            <input type="text" class="form-control borda-grossa" id="name" name="name" value="{{ $cliente->name ?? '' }}" required>
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="father_name" class="form-label mb-1 me-1">Nome do Pai:</label>
                            <input type="text" class="form-control borda-grossa" id="father_name" name="father_name" value="{{ $cliente->father_name ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="mother_name" class="form-label mb-1 me-1">Nome da Mãe:</label>
                            <input type="text" class="form-control borda-grossa" id="mother_name" name="mother_name" value="{{ $cliente->mother_name ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="address" class="form-label mb-1 me-1">Endereço:</label>
                            <input type="text" class="form-control borda-grossa" id="address" name="address" value="{{ $cliente->address ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="city" class="form-label mb-1 me-1">Cidade</label>
                            <input type="text" class="form-control borda-grossa" id="city" name="city" value="{{ $cliente->city ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="state" class="form-label mb-1 me-1">Estado:</label>
                            <input type="text" class="form-control borda-grossa" id="state" name="state" value="{{ $cliente->state ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="house_number" class="form-label mb-1 me-1">Número:</label>
                            <input type="text" class="form-control borda-grossa" id="house_number" name="house_number" value="{{ $cliente->house_number ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="neighborhood" class="form-label mb-1 me-1">Bairro:</label>
                            <input type="text" class="form-control borda-grossa" id="neighborhood" name="neighborhood" value="{{ $cliente->neighborhood ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="zip_code" class="form-label mb-1 me-1">CEP:</label>
                            <input type="text" class="form-control borda-grossa" id="zip_code" name="zip_code" value="{{ $cliente->zip_code ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="mobile_phone" class="form-label mb-1 me-1">Celular:</label>
                            <input type="text" class="form-control borda-grossa" id="mobile_phone" name="mobile_phone" value="{{ $cliente->mobile_phone ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="phone" class="form-label mb-1 me-1">Telefone:</label>
                            <input type="text" class="form-control borda-grossa" id="phone" name="phone" value="{{ $cliente->phone ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="secondary_phone" class="form-label mb-1 me-1">Telefone para Recado:</label>
                            <input type="text" class="form-control borda-grossa" id="secondary_phone" name="secondary_phone" value="{{ $cliente->secondary_phone ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="marital_status" class="form-label mb-1 me-1">Estado Civil:</label>
                            <input type="text" class="form-control borda-grossa" id="marital_status" name="marital_status" value="{{ $cliente->marital_status ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="profession" class="form-label mb-1 me-1">Profissão:</label>
                            <input type="text" class="form-control borda-grossa" id="profession" name="profession" value="{{ $cliente->profession ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="tax_id" class="form-label mb-1 me-1">CPF:</label>
                            <input type="text" class="form-control borda-grossa" id="tax_id" name="tax_id" value="{{ $cliente->tax_id ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="identity_card" class="form-label mb-1 me-1">RG:</label>
                            <input type="text" class="form-control borda-grossa" id="identity_card" name="identity_card" value="{{ $cliente->identity_card ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="identity_card_issuer" class="form-label mb-1 me-1">Orgão emissor do RG:</label>
                            <input type="text" class="form-control borda-grossa" id="identity_card_issuer" name="identity_card_issuer" value="{{ $cliente->identity_card_issuer ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="identity_card_issue_date" class="form-label mb-1 me-1">Data da Emissão do RG:</label>
                            <input type="text" class="form-control borda-grossa" id="identity_card_issue_date" name="identity_card_issue_date" value="{{ $cliente->identity_card_issue_date ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="voter_id" class="form-label mb-1 me-1">Título de Eleitor:</label>
                            <input type="text" class="form-control borda-grossa" id="voter_id" name="voter_id" value="{{ $cliente->voter_id ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="work_card" class="form-label mb-1 me-1">Carteira de Trabalho:</label>
                            <input type="text" class="form-control borda-grossa" id="work_card" name="work_card" value="{{ $cliente->work_card ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="rgp" class="form-label mb-1 me-1">RGP:</label>
                            <input type="text" class="form-control borda-grossa" id="rgp" name="rgp" value="{{ $cliente->rgp ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="rgp_issue_date" class="form-label mb-1 me-1">Data da RGP:</label>
                            <input type="text" class="form-control borda-grossa" id="rgp_issue_date" name="rgp_issue_date" value="{{ $cliente->rgp_issue_date ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="pis" class="form-label mb-1 me-1">PIS:</label>
                            <input type="text" class="form-control borda-grossa" id="pis" name="pis" value="{{ $cliente->pis ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="cei" class="form-label mb-1 me-1">CEI:</label>
                            <input type="text" class="form-control borda-grossa" id="cei" name="cei" value="{{ $cliente->cei ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="drivers_license" class="form-label mb-1 me-1">CNH:</label>
                            <input type="text" class="form-control borda-grossa" id="drivers_license" name="drivers_license" value="{{ $cliente->drivers_license ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="license_issue_date" class="form-label mb-1 me-1">Data da emissão da CNH:</label>
                            <input type="text" class="form-control borda-grossa" id="license_issue_date" name="license_issue_date" value="{{ $cliente->license_issue_date ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="email" class="form-label mb-1 me-1">Email:</label>
                            <input type="email" class="form-control borda-grossa" id="email" name="email" value="{{ $cliente->email ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="affiliation" class="form-label mb-1 me-1">Filiação:</label>
                            <input type="text" class="form-control borda-grossa" id="affiliation" name="affiliation" value="{{ $cliente->affiliation ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="birth_date" class="form-label mb-1 me-1">Nascimento:</label>
                            <input type="text" class="form-control borda-grossa" id="birth_date" name="birth_date" value="{{ $cliente->birth_date ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="birth_place" class="form-label mb-1 me-1">Local de nascimento:</label>
                            <input type="text" class="form-control borda-grossa" id="birth_place" name="birth_place" value="{{ $cliente->birth_place ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="expiration_date" class="form-label mb-1 me-1">Vencimento:</label>
                            <input type="text" class="form-control borda-grossa" id="expiration_date" name="expiration_date" value="{{ $cliente->expiration_date ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="notes" class="form-label mb-1 me-1">Senha:</label>
                            <input type="text" class="form-control borda-grossa" id="notes" name="notes" value="{{ $cliente->notes ?? '' }}">
                        </div>

                        @if(isset($cliente))
                        <div class="align-items-end justify-content-between">
                            @method('PUT')
                            @csrf
                            <div class="text-end">
                                <button type="submit" class="btn btn-success px-5 py-2 mt-2">
                                    Salvar
                                </button>
                            </div>
                            @else
                            @csrf
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-5 py-2 mt-2">
                                    Cadastrar pescador
                                </button>
                            </div>
                            @endif
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            @if($inadimplente)
            <div class="p-4 border rounded shadow-sm" id="cardInadimplente">
                <div id="alertaInadimplente" class="alert alert-danger">
                    O pescador está inadimplente. Documentos não estão disponíveis.
                </div>

                <a href="#" id="autorizacaoLink" class="alert-link text-decoration-underline">
                    Recebi autorização dos administradores
                </a>
            </div>
                @endif
                <div id="documentosPescador" @class(['d-none'=> $inadimplente, 'd-block' => !$inadimplente])>
                    <!-- Conteúdo dos documentos aqui -->
                    @if(isset($cliente))
                    <h3 class="mb-3 nowrap">Documentos do Pescador</h3>

                    <!-- Botões principais -->
                    <div class="d-grid gap-2 mb-4" role="group" aria-label="Arquivos do pescador">
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#arquivosModal" data-cliente-id="{{ $cliente->id ?? '' }}">
                            <i class="bi bi-folder2-open"></i> Exibir arquivos do pescador
                        </button>
                        <button class="btn btn-success" type="button" id="uploadBtn" data-bs-toggle="modal" data-bs-target="#uploadModal" data-cliente-id="{{ $cliente->id ?? '' }}">
                            <i class="bi bi-upload"></i> Upload de arquivos
                        </button>
                        <!-- @method('POST')
                        <form method="POST" class="d-grid gap-2" action="{{ route('pescadores.receiveAnnual', $cliente->id) }}" style="display:inline;" onsubmit="return confirm('Receber deste pescador ? {{ $cliente->name }}');">
                            @csrf
                            <button type="submit" class="btn btn-info">Receber anuidade</button>
                        </form> -->
                    </div>

                    <!-- Lista de documentos -->
                    <div class="list-group" class="lista-documentos">
                        <h2 class="mb-3">Imprimir</h2>
                        <h5 class="mb-2">Documentos Disponíveis:</h5>
                        <a href="{{ route('ruralActivity', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de exercício de atividade rural</a>
                        <a href="{{ route('president_Dec', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração do Presidente</a>
                        <a href="{{ route('auto_Dec', $cliente->id) }}" class="list-group-item list-group-item-action">Autodeclaração do segurado especial (nova)</a>
                        <a href="{{ route('insurance_Auth', $cliente->id) }}" class="list-group-item list-group-item-action">Termo de autorização para solicitação de seguro</a>
                        <a href="{{ route('previdence_Auth', $cliente->id) }}" class="list-group-item list-group-item-action">Termo de representação e autorização de acesso a informações previdenciárias</a>
                        <a href="{{ route('licence_Requirement', $cliente->id) }}" class="list-group-item list-group-item-action">Formulário de requerimento de licença</a>
                        <a href="{{ route('residence_Dec', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de residência</a>
                        <a href="{{ route('affiliation_Dec', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de filiação</a>
                        <a href="{{ route('registration_Form', $cliente->id) }}" class="list-group-item list-group-item-action">Ficha da Colônia</a>
                        <a href="{{ route('seccond_Via_Reciept', $cliente->id) }}" class="list-group-item list-group-item-action">Segunda via do recibo</a>
                        <a href="{{ route('social_Security_Guide', $cliente->id) }}" class="list-group-item list-group-item-action">Guia da Previdência Social</a>
                        <a href="{{ route('INSS_Representation_Term', $cliente->id) }}" class="list-group-item list-group-item-action">Termo de representação ao INSS</a>
                        <a href="{{ route('dissemination', $cliente->id) }}" class="list-group-item list-group-item-action">Desfiliação</a>
                        <a href="{{ route('dec_Income', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de renda</a>
                        <a href="{{ route('dec_Third_Residence', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de residência (de terceiro)</a>
                        <a href="{{ route('dec_Own_Residence', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de residência (própria)</a>
                        <a href="{{ route('dec_New_Residence', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de residência (nova)</a>
                        <a href="{{ route('seccond_Check', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de segunda via</a>
                        <a href="{{ route('PIS', $cliente->id) }}" class="list-group-item list-group-item-action">PIS</a>
                    </div>
                    @endif
                
            </div>
            
        </div>
        <!-- Script Corrigido -->
        <!-- <script>
            document.addEventListener('DOMContentLoaded', function() {
                const link = document.getElementById('autorizacaoLink');
                const alerta = document.getElementById('alertaInadimplente');
                const documentos = document.getElementById('documentosPescador');

                if (link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();

                        // Remove o alerta
                        if (alerta) {
                            alerta.style.display = 'none';
                            link.style.display = 'none';
                            documentos.classList.remove('d-none');
                            documentos.classList.add('d-block');
                        }

                        // Mostra os documentos
                        // if (documentos) {
                        //     documentos.classList.remove('d-none');
                        //     documentos.classList.add('d-block');
                            
                        // }
                    });
                }
            });

                                    <div class="col-md-12 mb-1 d-flex">
                            <label for="caepf_code" class="form-label mb-1 me-1">Código de Acesso CAEPF:</label>
                            <input type="text" class="form-control borda-grossa" id="caepf_code" name="caepf_code" value="{{ $cliente->caepf_code ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-1 d-flex">
                            <label for="caepf_password" class="form-label mb-1 me-1">Senha CAEPF:</label>
                            <input type="password" class="form-control borda-grossa" id="caepf_password" name="caepf_password" value="{{ $cliente->caepf_password ?? '' }}">
                        </div>
        </script> -->
        @endsection