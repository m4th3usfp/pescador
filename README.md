# 🐟 Pescador IA — Sistema de Gestão de Colônias de Pescadores

Sistema Laravel 11 para cadastro e gestão de pescadores artesanais, com geração de documentos oficiais (.docx) para colônias em **Frutal**, **Uberlândia** e **Fronteira**.

---

## Sumário

- [Stack](#stack)
- [Arquitetura](#arquitetura)
- [Models (Banco de Dados)](#models-banco-de-dados)
- [Rotas](#rotas)
- [Fluxo de Geração de Documentos](#fluxo-de-geração-de-documentos)
- [Comandos Agendados](#comandos-agendados)
- [Views](#views)
- [Melhorias Aplicadas](#melhorias-aplicadas)
- [O Que Precisa Melhorar (Boas Práticas)](#o-que-precisa-melhorar-boas-práticas)

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Framework | Laravel 11 |
| PHP | 8.2+ |
| Banco | PostgreSQL |
| Frontend | Bootstrap 5, jQuery, DataTables, DayJS |
| Documentos | PhpOffice/PhpWord (.docx) |
| Storage | Local + Cloudflare R2 (S3) |
| Email | Laravel Mail (SMTP) |
| Audit | Spatie Activitylog |
| Pix | wandesnet/qrcode-pix-laravel |
| Fila | Database driver |

---

## Arquitetura

```
app/
├── Actions/           # 22 classes de geração de documentos (Action Pattern)
│   ├── BaseDocumentAction.php   # Classe abstrata com template method
│   └── Generate*.php           # Uma Action por tipo de documento
├── Console/Commands/  # 4 comandos (backup, pix, imports)
├── Data/
│   └── DocumentData.php        # DTO imutável para dados de documentos
├── Helpers/
│   └── ColonyHelper.php        # Resolve city_id da sessão
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php
│   │   └── FishermanController.php  # ~1638 linhas, controller principal
│   ├── Middleware/
│   │   └── CheckUserCity.php
│   └── Requests/
│       └── StoreFishermanRequest.php
├── Models/
│   ├── Fisherman.php
│   ├── User.php
│   ├── City.php
│   ├── Payment_Record.php
│   ├── Fisherman_Files.php
│   ├── Owner_Settings_Model.php
│   ├── Colony_Settings.php
│   ├── Anual.php
│   └── ActivityLog.php
└── Services/
    └── DocumentGeneratorService.php  # Service de geração de .docx

resources/
├── views/             # 9 blades (login, listagem, cadastro, pagamentos, logs, etc.)
└── templates/         # 40+ templates .docx (alguns por cidade: _1, _2, _3_vila)

routes/
├── web.php            # ~30 rotas autenticadas + públicas
└── console.php        # Agendamentos (backup 19h diário, pix dia 29 mensal)
```

---

## Models (Banco de Dados)

| Tabela | Finalidade |
|--------|-----------|
| `fishermen` | Dados dos pescadores (~40 colunas: nome, RG, CPF, RGP, PIS, vencimento, etc.) |
| `users` | Usuários do sistema (nome, cidade, senha) |
| `cities` | Cidades atendidas (Frutal=1, Uberlândia=2, Fronteira=3) |
| `payment_record` | Histórico de pagamentos/recebimentos de anuidade |
| `fisherman_files` | Metadados de arquivos enviados (S3) |
| `owner_settings` | Configurações da colônia por cidade (presidente, valores, endereço) |
| `colony_settings` | Chave-valor para dados dinâmicos (sequenciais, competências, INSS) |
| `activity_log` | Log de auditoria (Spatie) |
| `anual` | Valores de anuidade (legado) |

---

## Rotas

### Públicas
- `GET /login` — Formulário de login
- `POST /login` — Autenticação

### Autenticadas (middleware `auth`)
- `GET /listagem` — Lista de pescadores com DataTables + filtros
- `GET /Cadastro` / `POST /Cadastro` — Criar pescador (já gera recibo .docx)
- `GET /listagem/{id}` / `PUT /listagem/{id}` / `DELETE /listagem/{id}` — CRUD
- `POST /listagem/{id}` — Receber anuidade (renova + gera recibo)
- `GET /pagamento_registro` — Histórico de pagamentos por período
- `GET /log_registro` — Registro de atividades (audit log)
- `GET /fisherman/{id}/{documento}` — 19 endpoints de geração de documentos
- `POST /fisherman/{id}/upload_arquivo` — Upload de arquivos
- `DELETE /arquivos/{id}` — Excluir arquivo

---

## Fluxo de Geração de Documentos

O sistema possui **2 abordagens**:

### Approach 1: Métodos inline no Controller (legado)
Alguns documentos ainda têm a lógica diretamente no `FishermanController` (ex: `ruralActivity()`, `previdence_Auth()`, `licence_Requirement()`). Cada um monta seu próprio array `$data`, resolve template, processa e retorna download.

### Approach 2: Actions + DTO (refatorado)
A maioria dos documentos foi refatorada para usar o pipeline:

```
Controller -> Action.execute(id)
              ├── Busca Fisherman + Owner_Settings
              ├── Cria DocumentData via DocumentData::base()
              ├── buildData() adiciona campos específicos
              ├── DocumentGeneratorService.processAndSave()
              │   ├── Abre .docx com TemplateProcessor
              │   ├── setValue() para cada campo
              │   └── Salva em storage/app/public/
              └── activity()->log()
```

### Documentos disponíveis (19 tipos)

| Documento | Template | Por cidade? |
|-----------|----------|-------------|
| Atividade Rural | `decativrural` | Sim |
| Declaração do Presidente | `presidente` | Sim |
| Autodeclaração (nova) | `autodeclaracaonova` | Não |
| Termo de Seguro | `termoautorizacao` | Não |
| Termo de Informações Previdenciárias | `termo_info_previdenciarias` | Não |
| Formulário de Licença | `formulario` | Não |
| Declaração de Residência | `dec_residencia` | Não |
| Declaração de Filiação | `filiacao` | Não |
| Ficha da Colônia | `ficha` | Sim |
| 2ª Via do Recibo | `recibo` | Sim |
| Guia da Previdência Social | `guia` | Sim |
| Termo de Representação INSS | `termo` | Não |
| Desfiliação | `desfiliacao` | Sim |
| Declaração de Renda | `renda` | Sim |
| Residência Própria | `residencia_propria_new` | Não |
| Residência Terceiro | `residencia` | Não |
| Residência (novo) | `residencianovo` | Não |
| 2ª Via | `segunda_via` | Não |
| PIS | `pis` | Não |

---

## Comandos Agendados

| Comando | Agendamento | Descrição |
|---------|-------------|-----------|
| `app:backup` | Diário 19:00 | pg_dump → S3 → email |
| `app:generate-pix` | Dia 29 19:00 | Gera QR Code PIX R$300 → email |

### Comandos de importação (manuais)
- `import:fisherman {file}` — Importa CSV de pescadores
- `import:fisherman_files {file}` — Importa arquivos do S3 legado

---

## Views

| Blade | Função |
|-------|--------|
| `listagem.blade.php` | Tabela principal com DataTables, toggle de colunas, cores por vencimento |
| `Cadastro.blade.php` | Formulário de ~30 campos + sidebar de documentos + upload de arquivos |
| `payment.blade.php` | Filtro por período/cidade + tabela de pagamentos |
| `activity_log_table.blade.php` | Log de atividades com diff de alterações (antes/depois) |
| `Auth/login.blade.php` | Login com nome de usuário + senha |
| `receipt-pdf.blade.php` | Template HTML de recibo (legado, não usado) |

---

## Melhorias Aplicadas

Nesta conversa, os itens abaixo foram **discutidos mas não implementados** (deixados quietos a pedido):

- **Record_number duplicado** — Lógica `MAX()+1` sem lock, vulnerável a concorrência
- **Select de cidade pós-login** — Já existe na listagem; pendente se for pra ser antes
- **PIX/QR Code por email** — Comando `generatePix` hardcoded, sem integração por pescador
- **Declaração de filiação não alfabetizado** — Já removida

---

## O Que Precisa Melhorar (Boas Práticas)

### 🔴 Críticos

1. **Controller inchado** — `FishermanController` com ~1638 linhas. Quebrar em controllers especializados (`DocumentController`, `PaymentController`, `FileController`, `FishermanController` só pro CRUD).

2. **Lógica de documento duplicada** — Alguns documentos ainda usam código inline no controller (`ruralActivity`, `previdence_Auth`, `licence_Requirement`, `seccond_Via_Reciept`, `social_Security_Guide`, `PIS`) enquanto outros foram refatorados pra Actions. Unificar tudo no padrão Action + DTO.

3. **Record_number sem lock** — `MAX(record_number) + 1` fora de transação. Usar `DB::transaction` + `lockForUpdate()` pra evitar concorrência.

4. **Regras de autorização inline** — `Auth::user()->name === 'Matheus'` espalhado em várias views e controllers. Criar uma **Gate/Policy** ou usar **Spatie Permission** pra controle de acesso por papel (admin, supervisor, usuário).

### 🟡 Importantes

5. **StoreFishermanRequest com regras muito permissivas** — Quase todos os campos são `nullable|string|max:255`. Validar CPF, RG, CEP, telefone com formatos específicos. Usar `required` nos campos obrigatórios.

6. **Data migration manual** — As queries de INSERT no `rascunho.txt` mostram que dados foram migrados na mão. Criar uma **seeder** ou **migration** própria pra dados iniciais.

7. **Variáveis de ambiente sem fallback** — `env('AWS_URL')` no controller. Usar `config()` com valores default.

8. **User::getAuthPassword() apontando pra coluna errada?** — Model tenta usar `senha` mas migration cria `password`. Verificar qual coluna realmente existe.

9. **TrimStrings duplicado** — `TrimString.php` e `TrimStrings.php` fazem a mesma coisa.

### 🔵 Boas Práticas / Manutenibilidade

10. **Nomear Actions em inglês** — Mistura de `GenerateDisseminationAction` com comentários em português. Padronizar o idioma.

11. **Tratar erros de forma consistente** — Alguns lugares usam `abort(404)`, outros `redirect()->back()->with('error')`, outros `response()->json()`. Criar um **Exception Handler** unificado.

12. **Testes** — Apenas `tests/Feature/AuthTest.php` existe. Sem testes para CRUD, geração de documentos, upload.

13. **DTO subutilizado** — `DocumentData` foi criado mas vários documentos ainda montam `$data` manualmente. Migrar todos pra usar `DocumentData::base()`.

14. **Remover views/assets legados** — `loginTEST.blade.php`, `welcome.blade.php`, `receipt-pdf.blade.php` não são usados.

15. **Colony_Settings chave-valor vs tabela real** — Dados como sequenciais, competências INSS, bienênio poderiam ser colunas em tabelas específicas em vez de chave-valor genérico.

16. **Frontend mistura jQuery + JS puro** — Padronizar (ou migrar pra Alpine/Livewire se for evoluir).
