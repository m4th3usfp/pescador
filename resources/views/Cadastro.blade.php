@extends('layouts.app')
@section('title', 'Cadastrar Usuário')
@section('content')
<div class="container mt-4 p-4 border rounded shadow-sm">
    <h2 class="mb-3">Cadastro Colônia</h2>
    <form method="POST" action="{{ route('store') }}">
        @csrf
        <div class="justify-content-end d-flex me-4">
            <button type="submit" class="btn btn-primary w-25">Cadastrar pescador</button>
        </div>
        <div class="container row g-3">
            <div class="col-md-4">
                <label for="record_number" class="form-label">Ficha</label>
                <input type="text" class="form-control" id="record_number" name="record_number">
            </div>
            <div class="col-md-8">
                <label for="name" class="form-label">nome</label>
                <input type="text" class="form-control" id="name" name="name">
            </div>
            <div class="col-md-6">
                <label for="father_name" class="form-label">Nome do Pai</label>
                <input type="text" class="form-control" id="father_name" name="father_name">
            </div>
            <div class="col-md-6">
                <label for="mother_name" class="form-label">Nome da Mãe</label>
                <input type="text" class="form-control" id="mother_name" name="mother_name">
            </div>
            <div class="col-md-12">
                <label for="address" class="form-label">Endereço</label>
                <input type="text" class="form-control" id="address" name="address">
            </div>
            <div class="col-md-4">
                <label for="city" class="form-label">Cidade</label>
                <input type="text" class="form-control" id="city" name="city">
            </div>
            @endsection
            <!-- <div class="col-md-4">
                <label for="house_number" class="form-label">Número</label>
                <input type="text" class="form-control" id="house_number" name="house_number">
            </div>
            <div class="col-md-4">
                <label for="neighborhood" class="form-label">Bairro</label>
                <input type="text" class="form-control" id="neighborhood" name="neighborhood">
            </div>
            <div class="col-md-4">
                <label for="state" class="form-label">Estado</label>
                <input type="text" class="form-control" id="state" name="state">
            </div>
            <div class="col-md-4">
                <label for="zip_code" class="form-label">CEP</label>
                <input type="text" class="form-control" id="zip_code" name="zip_code">
            </div>
            <div class="col-md-4">
                <label for="mobile_phone" class="form-label">Celular</label>
                <input type="text" class="form-control" id="mobile_phone" name="mobile_phone">
            </div>
            <div class="col-md-4">
                <label for="phone" class="form-label">Telefone</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>
            <div class="col-md-4">
                <label for="secondary_phone" class="form-label">Telefone para Recado</label>
                <input type="text" class="form-control" id="secondary_phone" name="secondary_phone">
            </div>
            <div class="col-md-4">
                <label for="marital_status" class="form-label">Estado Civil</label>
                <input type="text" class="form-control" id="marital_status" name="marital_status">
            </div>
            <div class="col-md-4">
                <label for="profession" class="form-label">Profissão</label>
                <input type="text" class="form-control" id="profession" name="profession">
            </div>
            <div class="col-md-4">
                <label for="tax_id" class="form-label">CPF</label>
                <input type="text" class="form-control" id="tax_id" name="tax_id">
            </div>
            <div class="col-md-4">
                <label for="identity_card" class="form-label">RG</label>
                <input type="text" class="form-control" id="identity_card" name="identity_card">
            </div>
            <div class="col-md-4">
                <label for="identity_card_issuer" class="form-label">Orgão emissor do RG</label>
                <input type="text" class="form-control" id="identity_card_issuer" name="identity_card_issuer">
            </div>
            <div class="col-md-4">
                <label for="identity_card_issue_date" class="form-label">Data da Emissão do RG</label>
                <input type="text" class="form-control" id="identity_card_issue_date" name="identity_card_issue_date">
            </div>
            <div class="col-md-4">
                <label for="voter_id" class="form-label">Título de Eleitor</label>
                <input type="text" class="form-control" id="voter_id" name="voter_id">
            </div>
            <div class="col-md-4">
                <label for="work_card" class="form-label">Carteira de Trabalho</label>
                <input type="text" class="form-control" id="work_card" name="work_card">
            </div>
            <div class="col-md-4">
                <label for="rgp" class="form-label">RGP</label>
                <input type="text" class="form-control" id="rgp" name="rgp">
            </div>
            <div class="col-md-4">
                <label for="rgp_issue_date" class="form-label">Data da RGP</label>
                <input type="text" class="form-control" id="rgp_issue_date" name="rgp_issue_date">
            </div>
            <div class="col-md-4">
                <label for="pis" class="form-label">PIS</label>
                <input type="text" class="form-control" id="pis" name="pis">
            </div>
            <div class="col-md-4">
                <label for="cei" class="form-label">CEI</label>
                <input type="text" class="form-control" id="cei" name="cei">
            </div>
            <div class="col-md-4">
                <label for="drivers_license" class="form-label">CNH</label>
                <input type="text" class="form-control" id="drivers_license" name="drivers_license">
            </div>
            <div class="col-md-4">
                <label for="license_issue_date" class="form-label">Data da emissão da CNH</label>
                <input type="text" class="form-control" id="license_issue_date" name="license_issue_date">
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="col-md-4">
                <label for="affiliation" class="form-label">Filiação</label>
                <input type="text" class="form-control" id="affiliation" name="affiliation">
            </div>
            <div class="col-md-4">
                <label for="birth_date" class="form-label">Nascimento</label>
                <input type="text" class="form-control" id="birth_date" name="birth_date">
            </div>
            <div class="col-md-4">
                <label for="birth_place" class="form-label">Local de nascimento</label>
                <input type="text" class="form-control" id="birth_place" name="birth_place">
            </div>
            <div class="col-md-4">
                <label for="expiration_date" class="form-label">Vencimento</label>
                <input type="text" class="form-control" id="expiration_date" name="expiration_date">
            </div>
            <div class="col-md-4">
                <label for="notes" class="form-label">Senha</label>
                <input type="text" class="form-control" id="notes" name="notes">
            </div>
            <div class="col-md-4">
                <label for="foreman" class="form-label">Capataz</label>
                <input type="text" class="form-control" id="foreman" name="foreman">
            </div>
            <div class="col-md-4">
                <label for="caepf_code" class="form-label">Código de Acesso CAEPF</label>
                <input type="text" class="form-control" id="caepf_code" name="caepf_code">
            </div>
            <div class="col-md-4">
                <label for="caepf_password" class="form-label">Senha CAEPF</label>
                <input type="password" class="form-control" id="caepf_password" name="caepf_password">
            </div>
        </div>
    </form>
</div> -->