@extends('layouts.app')
@section('title', 'Cadastrar Usuário')
@section('content')
<div class="container mt-4">
    <div class="row">
        <!-- Coluna Esquerda (Formulário existente) -->
        <div class="{{ isset($cliente) ? 'col-md-8 pe-4' : 'col-md-12' }}">
            <div class="p-4 border rounded shadow-sm">
                <h2 class="mb-3">{{ isset($cliente) ? 'Editar pescador' : 'Cadastrar pescador' }}</h2>
                <a href="{{ route('listagem') }}" class="btn btn-outline-secondary">
                    listagem
                </a>
                @if(isset($cliente))
                <a href="{{ route('listagem') }}" class="btn btn-info">
                    Receber anuidade
                </a>
                @endif
                <form method="POST" action="{{ isset($cliente) ? route('pescadores.update', $cliente->id) : route('store') }}">
                    @csrf
                    @if(isset($cliente))
                    @method('PUT')
                    <div class="justify-content-end d-flex me-4">
                        <button type="submit" class="btn btn-primary w-25">
                            Salvar
                        </button>
                    </div>
                    @else
                    <div class="justify-content-end d-flex me-4">
                        <button type="submit" class="btn btn-primary w-25">
                            Cadastrar pescador
                        </button>
                    </div>
                    @endif
                    <div class="container row g-3">
                        <div class="col-md-4">
                            <label for="record_number" class="form-label">Ficha</label>
                            <input type="text" class="form-control" id="record_number" name="record_number" value="{{ $recordNumber }}" readonly>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">nome</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $cliente->name ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label for="father_name" class="form-label">Nome do Pai</label>
                            <input type="text" class="form-control" id="father_name" name="father_name" value="{{ $cliente->father_name ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label for="mother_name" class="form-label">Nome da Mãe</label>
                            <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ $cliente->father_name ?? '' }}">
                        </div>
                        <div class="col-md-12">
                            <label for="address" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ $cliente->address ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="city" name="city" value="{{ $cliente->city ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">Estado</label>
                            <input type="text" class="form-control" id="state" name="state" value="{{ $cliente->state ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="house_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="house_number" name="house_number" value="{{ $cliente->house_number ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="neighborhood" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="neighborhood" name="neighborhood" value="{{ $cliente->neighborhood ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="zip_code" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" value="{{ $cliente->zip_code ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="mobile_phone" class="form-label">Celular</label>
                            <input type="text" class="form-control" id="mobile_phone" name="mobile_phone" value="{{ $cliente->mobile_phone ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ $cliente->phone ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="secondary_phone" class="form-label">Telefone para Recado</label>
                            <input type="text" class="form-control" id="secondary_phone" name="secondary_phone" value="{{ $cliente->secondary_phone ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="marital_status" class="form-label">Estado Civil</label>
                            <input type="text" class="form-control" id="marital_status" name="marital_status" value="{{ $cliente->marital_status ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="profession" class="form-label">Profissão</label>
                            <input type="text" class="form-control" id="profession" name="profession" value="{{ $cliente->profession ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="tax_id" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id" value="{{ $cliente->tax_id ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="identity_card" class="form-label">RG</label>
                            <input type="text" class="form-control" id="identity_card" name="identity_card" value="{{ $cliente->identity_card ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="identity_card_issuer" class="form-label">Orgão emissor do RG</label>
                            <input type="text" class="form-control" id="identity_card_issuer" name="identity_card_issuer" value="{{ $cliente->identity_card_issuer ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="identity_card_issue_date" class="form-label">Data da Emissão do RG</label>
                            <input type="text" class="form-control" id="identity_card_issue_date" name="identity_card_issue_date" value="{{ $cliente->identity_card_issue_date ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="voter_id" class="form-label">Título de Eleitor</label>
                            <input type="text" class="form-control" id="voter_id" name="voter_id" value="{{ $cliente->voter_id ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="work_card" class="form-label">Carteira de Trabalho</label>
                            <input type="text" class="form-control" id="work_card" name="work_card" value="{{ $cliente->work_card ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="rgp" class="form-label">RGP</label>
                            <input type="text" class="form-control" id="rgp" name="rgp" value="{{ $cliente->rgp ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="rgp_issue_date" class="form-label">Data da RGP</label>
                            <input type="text" class="form-control" id="rgp_issue_date" name="rgp_issue_date" value="{{ $cliente->rgp_issue_date ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="pis" class="form-label">PIS</label>
                            <input type="text" class="form-control" id="pis" name="pis" value="{{ $cliente->pis ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="cei" class="form-label">CEI</label>
                            <input type="text" class="form-control" id="cei" name="cei" value="{{ $cliente->cei ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="drivers_license" class="form-label">CNH</label>
                            <input type="text" class="form-control" id="drivers_license" name="drivers_license" value="{{ $cliente->drivers_license ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="license_issue_date" class="form-label">Data da emissão da CNH</label>
                            <input type="text" class="form-control" id="license_issue_date" name="license_issue_date" value="{{ $cliente->license_issue_date ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $cliente->email ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="affiliation" class="form-label">Filiação</label>
                            <input type="text" class="form-control" id="affiliation" name="affiliation" value="{{ $cliente->affiliation ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="birth_date" class="form-label">Nascimento</label>
                            <input type="text" class="form-control" id="birth_date" name="birth_date" value="{{ $cliente->birth_date ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="birth_place" class="form-label">Local de nascimento</label>
                            <input type="text" class="form-control" id="birth_place" name="birth_place" value="{{ $cliente->birth_place ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="expiration_date" class="form-label">Vencimento</label>
                            <input type="text" class="form-control" id="expiration_date" name="expiration_date" value="{{ $cliente->expiration_date ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="notes" class="form-label">Senha</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="{{ $cliente->notes ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="foreman" class="form-label">Capataz</label>
                            <input type="text" class="form-control" id="foreman" name="foreman" value="{{ $cliente->foreman ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="caepf_code" class="form-label">Código de Acesso CAEPF</label>
                            <input type="text" class="form-control" id="caepf_code" name="caepf_code" value="{{ $cliente->caepf_code ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label for="caepf_password" class="form-label">Senha CAEPF</label>
                            <input type="password" class="form-control" id="caepf_password" name="caepf_password" value="{{ $cliente->caepf_password ?? '' }}">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($cliente))
        <div class="col-md-4">
            <div class="p-4 border rounded shadow-sm">
                <h3 class="mb-3">Documentos do Pescador</h3>

                <!-- Botões principais -->
                <div class="d-grid gap-2 mb-4">
                    <button class="btn btn-primary" type="button">
                        <i class="bi bi-folder2-open"></i> Exibir arquivos do pescador
                    </button>
                    <button class="btn btn-success" type="button">
                        <i class="bi bi-upload"></i> Upload de arquivos
                    </button>
                </div>

                <!-- Lista de documentos -->
                <div class="list-group">
                    <h2 class="mb-3">Imprimir:</h2>
                    <h5 class="mb-2">Documentos Disponíveis:</h5>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de exercício de atividade rural</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração do Presidente</a>
                    <a href="#" class="list-group-item list-group-item-action">Autodeclaração do segurado especial (nova)</a>
                    <a href="#" class="list-group-item list-group-item-action">Termo de autorização para solicitação de seguro</a>
                    <a href="#" class="list-group-item list-group-item-action">Termo de representação e autorização de acesso a informações previdenciárias</a>
                    <a href="#" class="list-group-item list-group-item-action">Formulário de requerimento de licença</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de filiação - MPA (não alfabetizado)</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de residência</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de filiação</a>
                    <a href="#" class="list-group-item list-group-item-action">Ficha da Colônia</a>
                    <a href="#" class="list-group-item list-group-item-action">Segunda via do recibo</a>
                    <a href="#" class="list-group-item list-group-item-action">Guia da Previdência Social</a>
                    <a href="#" class="list-group-item list-group-item-action">Termo de representação ao INSS</a>
                    <a href="#" class="list-group-item list-group-item-action">Desfiliação</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de renda</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de residência (de terceiro)</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de residência (própria)</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de residência (nova)</a>
                    <a href="#" class="list-group-item list-group-item-action">Declaração de segunda via</a>
                    <a href="#" class="list-group-item list-group-item-action">PIS</a>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
<!-- <div class="container mt-4 p-4 border rounded shadow-sm">
    <h2 class="mb-3">{{ isset($cliente) ? 'Editar pescador' : 'Cadastrar pescador' }}</h2>
    <a href="{{ route('listagem') }}" class="btn btn-outline-secondary">
        listagem
    </a>
    <form method="POST" action="{{ isset($cliente) ? route('pescadores.update', $cliente->id) : route('store') }}">
        @csrf
        @if(isset($cliente))
        @method('PUT')
        <div class="justify-content-end d-flex me-4">
            <button type="submit" class="btn btn-primary w-25">
                Salvar
            </button>
        </div>
        @else
        <div class="justify-content-end d-flex me-4">
            <button type="submit" class="btn btn-primary w-25">
                Cadastrar pescador
            </button>
        </div>
        @endif
        <div class="container row g-3">
            <div class="col-md-4">
                <label for="record_number" class="form-label">Ficha</label>
                <input type="text" class="form-control" id="record_number" name="record_number" value="{{ $recordNumber }}">
            </div>
            <div class="col-md-8">
                <label for="name" class="form-label">nome</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $cliente->name ?? '' }}">
            </div>
            <div class="col-md-6">
                <label for="father_name" class="form-label">Nome do Pai</label>
                <input type="text" class="form-control" id="father_name" name="father_name" value="{{ $cliente->father_name ?? '' }}">
            </div>
            <div class="col-md-6">
                <label for="mother_name" class="form-label">Nome da Mãe</label>
                <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ $cliente->father_name ?? '' }}">
            </div>
            <div class="col-md-12">
                <label for="address" class="form-label">Endereço</label>
                <input type="text" class="form-control" id="address" name="address" value="{{ $cliente->address ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="city" class="form-label">Cidade</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ $cliente->city ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="state" class="form-label">Estado</label>
                <input type="text" class="form-control" id="state" name="state" value="{{ $cliente->state ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="house_number" class="form-label">Número</label>
                <input type="text" class="form-control" id="house_number" name="house_number" value="{{ $cliente->house_number ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="neighborhood" class="form-label">Bairro</label>
                <input type="text" class="form-control" id="neighborhood" name="neighborhood" value="{{ $cliente->neighborhood ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="zip_code" class="form-label">CEP</label>
                <input type="text" class="form-control" id="zip_code" name="zip_code" value="{{ $cliente->zip_code ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="mobile_phone" class="form-label">Celular</label>
                <input type="text" class="form-control" id="mobile_phone" name="mobile_phone" value="{{ $cliente->mobile_phone ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="phone" class="form-label">Telefone</label>
                <input type="text" class="form-control" id="phone" name="phone" value="{{ $cliente->phone ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="secondary_phone" class="form-label">Telefone para Recado</label>
                <input type="text" class="form-control" id="secondary_phone" name="secondary_phone" value="{{ $cliente->secondary_phone ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="marital_status" class="form-label">Estado Civil</label>
                <input type="text" class="form-control" id="marital_status" name="marital_status" value="{{ $cliente->marital_status ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="profession" class="form-label">Profissão</label>
                <input type="text" class="form-control" id="profession" name="profession" value="{{ $cliente->profession ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="tax_id" class="form-label">CPF</label>
                <input type="text" class="form-control" id="tax_id" name="tax_id" value="{{ $cliente->tax_id ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="identity_card" class="form-label">RG</label>
                <input type="text" class="form-control" id="identity_card" name="identity_card" value="{{ $cliente->identity_card ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="identity_card_issuer" class="form-label">Orgão emissor do RG</label>
                <input type="text" class="form-control" id="identity_card_issuer" name="identity_card_issuer" value="{{ $cliente->identity_card_issuer ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="identity_card_issue_date" class="form-label">Data da Emissão do RG</label>
                <input type="text" class="form-control" id="identity_card_issue_date" name="identity_card_issue_date" value="{{ $cliente->identity_card_issue_date ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="voter_id" class="form-label">Título de Eleitor</label>
                <input type="text" class="form-control" id="voter_id" name="voter_id" value="{{ $cliente->voter_id ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="work_card" class="form-label">Carteira de Trabalho</label>
                <input type="text" class="form-control" id="work_card" name="work_card" value="{{ $cliente->work_card ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="rgp" class="form-label">RGP</label>
                <input type="text" class="form-control" id="rgp" name="rgp" value="{{ $cliente->rgp ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="rgp_issue_date" class="form-label">Data da RGP</label>
                <input type="text" class="form-control" id="rgp_issue_date" name="rgp_issue_date" value="{{ $cliente->rgp_issue_date ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="pis" class="form-label">PIS</label>
                <input type="text" class="form-control" id="pis" name="pis" value="{{ $cliente->pis ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="cei" class="form-label">CEI</label>
                <input type="text" class="form-control" id="cei" name="cei" value="{{ $cliente->cei ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="drivers_license" class="form-label">CNH</label>
                <input type="text" class="form-control" id="drivers_license" name="drivers_license" value="{{ $cliente->drivers_license ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="license_issue_date" class="form-label">Data da emissão da CNH</label>
                <input type="text" class="form-control" id="license_issue_date" name="license_issue_date" value="{{ $cliente->license_issue_date ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ $cliente->email ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="affiliation" class="form-label">Filiação</label>
                <input type="text" class="form-control" id="affiliation" name="affiliation" value="{{ $cliente->affiliation ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="birth_date" class="form-label">Nascimento</label>
                <input type="text" class="form-control" id="birth_date" name="birth_date" value="{{ $cliente->birth_date ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="birth_place" class="form-label">Local de nascimento</label>
                <input type="text" class="form-control" id="birth_place" name="birth_place" value="{{ $cliente->birth_place ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="expiration_date" class="form-label">Vencimento</label>
                <input type="text" class="form-control" id="expiration_date" name="expiration_date" value="{{ $cliente->expiration_date ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="notes" class="form-label">Senha</label>
                <input type="text" class="form-control" id="notes" name="notes" value="{{ $cliente->notes ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="foreman" class="form-label">Capataz</label>
                <input type="text" class="form-control" id="foreman" name="foreman" value="{{ $cliente->foreman ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="caepf_code" class="form-label">Código de Acesso CAEPF</label>
                <input type="text" class="form-control" id="caepf_code" name="caepf_code" value="{{ $cliente->caepf_code ?? '' }}">
            </div>
            <div class="col-md-4">
                <label for="caepf_password" class="form-label">Senha CAEPF</label>
                <input type="password" class="form-control" id="caepf_password" name="caepf_password" value="{{ $cliente->caepf_password ?? '' }}">
            </div>
        </div>
    </form>
</div> -->