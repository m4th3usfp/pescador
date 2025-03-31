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
                <label for="ficha" class="form-label">Ficha</label>
                <input type="text" class="form-control" id="ficha" name="ficha">
            </div>
            <div class="col-md-8">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome">
            </div>
            <div class="col-md-6">
                <label for="pai" class="form-label">Nome do Pai</label>
                <input type="text" class="form-control" id="pai" name="pai">
            </div>
            <div class="col-md-6">
                <label for="mae" class="form-label">Nome da Mãe</label>
                <input type="text" class="form-control" id="mae" name="mae">
            </div>
            <div class="col-md-12">
                <label for="endereco" class="form-label">Endereço</label>
                <input type="text" class="form-control" id="endereco" name="endereco">
            </div>
            <div class="col-md-4">
                <label for="numero" class="form-label">Número</label>
                <input type="text" class="form-control" id="numero" name="numero">
            </div>
            <div class="col-md-4">
                <label for="bairro" class="form-label">Bairro</label>
                <input type="text" class="form-control" id="bairro" name="bairro">
            </div>
            <div class="col-md-4">
                <label for="cidade" class="form-label">Cidade</label>
                <input type="text" class="form-control" id="cidade" name="cidade">
            </div>
            <div class="col-md-4">
                <label for="estado" class="form-label">Estado</label>
                <input type="text" class="form-control" id="estado" name="estado">
            </div>
            <div class="col-md-4">
                <label for="cep" class="form-label">CEP</label>
                <input type="text" class="form-control" id="cep" name="cep">
            </div>
            <div class="col-md-4">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" class="form-control" id="celular" name="celular">
            </div>
            <div class="col-md-4">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" class="form-control" id="telefone" name="telefone">
            </div>
            <div class="col-md-4">
                <label for="tel_recado" class="form-label">Telefone para Recado</label>
                <input type="text" class="form-control" id="tel_recado" name="tel_recado">
            </div>
            <div class="col-md-4">
                <label for="estado_civil" class="form-label">Estado Civil</label>
                <input type="text" class="form-control" id="estado_civil" name="estado_civil">
            </div>
            <div class="col-md-4">
                <label for="profissao" class="form-label">Profissão</label>
                <input type="text" class="form-control" id="profissao" name="profissao">
            </div>
            <div class="col-md-4">
                <label for="cpf" class="form-label">CPF</label>
                <input type="text" class="form-control" id="cpf" name="cpf">
            </div>
            <div class="col-md-4">
                <label for="rg" class="form-label">RG</label>
                <input type="text" class="form-control" id="rg" name="rg">
            </div>
            <div class="col-md-4">
                <label for="orgao_emissor_rg" class="form-label">Orgão emissor do RG</label>
                <input type="text" class="form-control" id="orgao_emissor_rg" name="emissor_rg">
            </div>
            <div class="col-md-4">
                <label for="data_emissao_rg" class="form-label">Data da Emissão do RG</label>
                <input type="date" class="form-control" id="data_emissao_rg" name="data_emissao_rg">
            </div>
            <div class="col-md-4">
                <label for="titulo_eleitor" class="form-label">Título de Eleitor</label>
                <input type="text" class="form-control" id="titulo_eleitor" name="titulo_eleitor">
            </div>
            <div class="col-md-4">
                <label for="carteira_trabalho" class="form-label">Carteira de Trabalho</label>
                <input type="text" class="form-control" id="carteira_trabalho" name="carteira_trabalho">
            </div>
            <div class="col-md-4">
                <label for="rgp" class="form-label">RGP</label>
                <input type="date" class="form-control" id="rgp" name="rgp">
            </div>
            <div class="col-md-4">
                <label for="data_rgp" class="form-label">Data da RGP</label>
                <input type="date" class="form-control" id="data_rgp" name="data_rgp">
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
                <label for="cnh" class="form-label">CNH</label>
                <input type="text" class="form-control" id="cnh" name="cnh">
            </div>
            <div class="col-md-4">
                <label for="emissao_cnh" class="form-label">Data da emissão da CNH</label>
                <input type="text" class="form-control" id="emissao_cnh" name="emissao_cnh">
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="col-md-4">
                <label for="filiacao" class="form-label">Filiação</label>
                <input type="text" class="form-control" id="filiacao" name="filiacao">
            </div>
            <div class="col-md-4">
                <label for="nascimento" class="form-label">Nascimento</label>
                <input type="text" class="form-control" id="nascimento" name="nascimento">
            </div>
            <div class="col-md-4">
                <label for="local_nascimento" class="form-label">Local de nascimento</label>
                <input type="text" class="form-control" id="local_nascimento" name="local_nascimento">
            </div>
            <div class="col-md-4">
                <label for="vencimento" class="form-label">Vencimento</label>
                <input type="text" class="form-control" id="vencimento" name="vencimento">
            </div>
            <div class="col-md-4">
                <label for="senha" class="form-label">Senha</label>
                <input type="text" class="form-control" id="senha" name="senha">
            </div>
            <div class="col-md-4">
                <label for="capataz" class="form-label">Capataz</label>
                <input type="text" class="form-control" id="capataz" name="capataz">
            </div>
            <div class="col-md-4">
                <label for="codigo_caepf" class="form-label">Código de Acesso CAEPF</label>
                <input type="text" class="form-control" id="codigo_caepf" name="codigo_caepf">
            </div>
            <div class="col-md-4">
                <label for="senha_caepf" class="form-label">Senha CAEPF</label>
                <input type="password" class="form-control" id="senha_caepf" name="senha_caepf">
            </div>
        </div>
    </form>
</div>
@endsection