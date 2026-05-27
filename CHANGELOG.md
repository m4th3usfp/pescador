# Changelog

## [Unreleased] — 2026-05-27

### Added

- **Sistema de Roles**: coluna `role` na tabela `users` + Gates do Laravel para autorização granular (admin, supervisor, user)
- **Config centralizada**: `config/colony.php` com chaves `pix.*` (email, phone, amount, name, cpf, city)
- **Variáveis de ambiente**: `PIX_EMAIL`, `PIX_PHONE`, `PIX_AMOUNT`, `PIX_NAME`, `PIX_CPF`, `PIX_CITY`, `MAIL_BACKUP_AUTHOR`, `MAIL_FROM_NAME`
- **Documentação**: este changelog

### Changed

- **Controller extraído**: `FishermanController` (~1638 linhas) dividido em 5 controllers especializados:
  - `FishermanController` (341 linhas) — CRUD central
  - `DocumentController` — geração/download de documentos
  - `PaymentController` — gestão de pagamentos
  - `FileController` — upload/download de arquivos
  - `LogController` — visualização de logs
- **Geração de documentos**: migração 100% para Action Pattern (22 classes `Generate*Action` estendendo `BaseDocumentAction`)
- **Autorização**: `isAdmin()`, `isSupervisor()`, `canSwitchCity()` agora dependem exclusivamente da coluna `role`, removendo fallback por nome de usuário (`in_array($this->name, ['Matheus', 'Dabiane', 'LUCAS'])`)
- **Backup.php**: email do destinatário agora lê de `config('colony.pix.email')` em vez de hardcoded
- **generatePix.php**: todos os valores do PIX lidos de `config('colony.pix.*')` em vez de hardcoded
- **generatePix.php**: QR code embutido como base64 no HTML do email (data:image/png;base64) — não salva mais em disco, não depende de URL pública
- **generatePix.php**: `->from()` corrigido — usa `config('mail.from.author')` (email) e `config('mail.from.name')` (nome)
- **Migration `0001_01_01_000000_create_users_table.php`**: typo `constrainded` → `constrained`, tabela `cities` criada antes de `users` para foreign key válida
- **Middleware `CheckUserCity`**: `$user->cidade` → `$user->city`, `merge(['cidade' => ...])` → `merge(['city' => ...])`
- **`.env`**: `MAIL_BACKUP_AUTHOR` corrigido (removeu caracteres inválidos), `MAIL_FROM_NAME` adicionado, variáveis `PIX_*` adicionadas

### Fixed

- **Race condition em `record_number`**: `->lockForUpdate()` removido (proibido com agregadas no PostgreSQL), substituído por `pg_advisory_xact_lock(city_id)`
- **Autenticação quebrada**: `getAuthPassword()` removido de `app/Models/User.php` — método retornava `$this->senha` (coluna inexistente), Laravel já lê `password` por padrão
- **Tests incompatíveis com Pest 3.x**: `TestCase::setUp()` alterado de `public` para `protected`
- **Testes sem sessão de cidade**: adicionado `$this->withSession(['selected_city' => 'Frutal'])` no `DocumentAccessTest`
- **Testes com tipo incorreto**: `record_number` convertido para `(string)` no `AuthTest`
- **Testes com foreign key inválida**: `'city' => $city` adicionado ao factory no `GateTest`
- **Permissão negada no QR code**: `generatePix.php` não salva mais arquivo em disco, eliminando erro `file_put_contents(): Permission denied` no Sail

### Removed

- `app/Models/User.php`: método `getAuthPassword()` (inseguro e quebrado)
- `app/Console/Commands/Backup.php`: dependência de `config('colony.backup_email')` (removida, usa `pix.email`)
- `app/Console/Commands/generatePix.php`: linhas de `str_replace`, `base64_decode`, `file_put_contents`, `asset` (desnecessárias com base66 inline)
- Views legadas: `loginTEST`, `welcome`, `receipt-pdf`
- Middleware `TrimString` duplicado
- Chamadas a `env()` substituídas por `config()` no código de produção
- `.env.testing`: trocado de MySQL para PostgreSQL

### Security

- **Credenciais AWS/Gmail** ainda expostas no `.env` rastreado pelo git — pendente de remoção + rotação
- **`APP_DEBUG=true`** em produção — pendente de correção
