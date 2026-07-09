# Routes Map — ConsultaProd

> 123 rotas totais. Verificar via `php artisan route:list`.
> Todos os módulos (exceto Auth e sistema) exigem autenticação: `web → Authenticate → CheckActive → EnsurePasswordChanged`.

---

## Auth (Breeze)

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | /login | login | Auth\AuthenticatedSessionController@create |
| POST | /login | — | Auth\AuthenticatedSessionController@store |
| POST | /logout | logout | Auth\AuthenticatedSessionController@destroy |
| GET | /forgot-password | password.request | Auth\PasswordResetLinkController@create |
| POST | /forgot-password | password.email | Auth\PasswordResetLinkController@store |
| GET | /reset-password/{token} | password.reset | Auth\NewPasswordController@create |
| POST | /reset-password | password.store | Auth\NewPasswordController@store |
| GET | /change-password | password.change | Auth\ChangePasswordController@create |
| POST | /change-password | password.update.forced | Auth\ChangePasswordController@store |
| GET | /confirm-password | password.confirm | Auth\ConfirmablePasswordController@show |
| POST | /confirm-password | — | Auth\ConfirmablePasswordController@store |
| PUT | /password | password.update | Auth\PasswordController@update |
| GET | /profile | profile.edit | ProfileController@edit |
| PATCH | /profile | profile.update | ProfileController@update |
| DELETE | /profile | profile.destroy | ProfileController@destroy |

---

## Dashboard

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | / | — | Closure (redirect para dashboard) |
| GET | /dashboard | dashboard | HomeController@index |
| ANY | /painel | — | Redirect 301 → /dashboard |

---

## Admin (usuários)
> Middleware extra: `CheckRole:admin`

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | /admin | admin.dashboard | AdminController@dashboard |
| GET | /admin/users | admin.users | AdminController@users |
| POST | /admin/users | admin.users.store | AdminController@storeUser |
| GET | /admin/users/create | admin.users.create | AdminController@createUser |
| GET | /admin/users/{user}/edit | admin.users.edit | AdminController@editUser |
| PATCH | /admin/users/{user} | admin.users.update | AdminController@updateUser |
| DELETE | /admin/users/{user} | admin.users.destroy | AdminController@destroyUser |
| PATCH | /admin/users/{user}/toggle-status | admin.users.toggle-status | AdminController@toggleUserStatus |

---

## Prestador
> Middleware: `CheckRole:admin,operator`

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | /prestador | prestador.index | PrestadorController@index |
| POST | /prestador | prestador.store | PrestadorController@store |
| GET | /prestador/create | prestador.create | PrestadorController@create |
| GET | /prestador/{prestador} | prestador.show | PrestadorController@show |
| GET | /prestador/{prestador}/edit | prestador.edit | PrestadorController@edit |
| PUT\|PATCH | /prestador/{prestador} | prestador.update | PrestadorController@update |
| DELETE | /prestador/{prestador} | prestador.destroy | PrestadorController@destroy |
| PATCH | /prestador/{prestador}/toggle-status | prestador.toggle-status | PrestadorController@toggleStatus |
| GET | /prestador-import | prestador.import | PrestadorImportController@create |
| POST | /prestador-import | prestador.import.store | PrestadorImportController@store |
| GET | /prestador-import/preview | prestador.import.preview | PrestadorImportController@preview |
| POST | /prestador-import/apply | prestador.import.apply | PrestadorImportController@apply |

---

## Procedimento
> Middleware: `CheckRole:admin,operator`

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | /procedimento | procedimento.index | ProcedimentoController@index |
| POST | /procedimento | procedimento.store | ProcedimentoController@store |
| GET | /procedimento/create | procedimento.create | ProcedimentoController@create |
| GET | /procedimento/{procedimento} | procedimento.show | ProcedimentoController@show |
| GET | /procedimento/{procedimento}/edit | procedimento.edit | ProcedimentoController@edit |
| PUT\|PATCH | /procedimento/{procedimento} | procedimento.update | ProcedimentoController@update |
| DELETE | /procedimento/{procedimento} | procedimento.destroy | ProcedimentoController@destroy |
| GET | /procedimento-import | procedimento.import | ProcedimentoImportController@create |
| POST | /procedimento-import | procedimento.import.store | ProcedimentoImportController@store |
| GET | /procedimento-import/preview | procedimento.import.preview | ProcedimentoImportController@preview |
| POST | /procedimento-import/apply | procedimento.import.apply | ProcedimentoImportController@apply |

---

## CBO
> Middleware: `CheckRole:admin,operator`

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | /cbo | cbo.index | CboController@index |
| POST | /cbo | cbo.store | CboController@store |
| GET | /cbo/create | cbo.create | CboController@create |
| GET | /cbo/{cbo} | cbo.show | CboController@show |
| GET | /cbo/{cbo}/edit | cbo.edit | CboController@edit |
| PUT\|PATCH | /cbo/{cbo} | cbo.update | CboController@update |
| DELETE | /cbo/{cbo} | cbo.destroy | CboController@destroy |

---

## Cismetro
> Middleware: `CheckRole:admin,operator`

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | /cismetro | cismetro.index | CismetroController@index |
| POST | /cismetro | cismetro.store | CismetroController@store |
| GET | /cismetro/create | cismetro.create | CismetroController@create |
| GET | /cismetro/{cismetro} | cismetro.show | CismetroController@show |
| GET | /cismetro/{cismetro}/edit | cismetro.edit | CismetroController@edit |
| PUT\|PATCH | /cismetro/{cismetro} | cismetro.update | CismetroController@update |
| DELETE | /cismetro/{cismetro} | cismetro.destroy | CismetroController@destroy |

---

## Relatórios

### SIA Produção — tabela `s_prd`
Controller: `RelatorioController`

| Method | URI | Name | Action |
|---|---|---|---|
| GET | /relatorios | relatorios.index | index (página principal) |
| GET | /relatorios/fields | relatorios.fields | getFields (lista campos disponíveis) |
| GET | /relatorios/lookup | relatorios.lookup | getLookupData (dados para dropdowns) |
| POST | /relatorios/generate | relatorios.generate | generate (gera relatório lista) |
| POST | /relatorios/generate-matrix | relatorios.generate-matrix | generateMatrix (gera relatório matriz) |
| POST | /relatorios/debug | relatorios.debug | debug (mostra SQL gerado) |
| GET | /relatorios/test-excel | relatorios.test-excel | testExcel |
| GET | /relatorios/standalone | relatorios.standalone | — |

### APAC — tabelas `s_pap` + `s_apa`
Controller: `RelatorioApacController`

| Method | URI | Name | Action |
|---|---|---|---|
| GET | /relatorios/apac | relatorios.apac.index | index |
| GET | /relatorios/apac/fields | relatorios.apac.fields | getFields |
| GET | /relatorios/apac/lookup | relatorios.apac.lookup | getLookupData |
| POST | /relatorios/apac/generate | relatorios.apac.generate | generate |
| POST | /relatorios/apac/generate-matrix | relatorios.apac.generate-matrix | generateMatrix |

### BPI — tabela `s_bpi`
Controller: `RelatorioBpiController`

| Method | URI | Name | Action |
|---|---|---|---|
| GET | /relatorios/bpi | relatorios.bpi.index | index |
| GET | /relatorios/bpi/fields | relatorios.bpi.fields | getFields |
| GET | /relatorios/bpi/lookup | relatorios.bpi.lookup | getLookupData |
| POST | /relatorios/bpi/generate | relatorios.bpi.generate | generate |
| POST | /relatorios/bpi/generate-matrix | relatorios.bpi.generate-matrix | generateMatrix |
| POST | /relatorios/bpi/debug | relatorios.bpi.debug | debug |

### Faturamento por Prestador — hierárquico (s_prd)
Controller: `FaturamentoPrestadorController`
> Filtros obrigatórios: `competencia` (AAAAMM). Opcional: `prestador_id`.

| Method | URI | Name | Action |
|---|---|---|---|
| GET | /relatorios/faturamento-prestador | faturamento-prestador.index | index |
| POST | /relatorios/faturamento-prestador/gerar | faturamento-prestador.gerar | gerar |
| POST | /relatorios/faturamento-prestador/pdf | faturamento-prestador.pdf | exportPdf |

---

## Dados SIA — CRUDs

### SAPA (tabela `s_apa`)
Controller: `SApaController`

| Method | URI | Name |
|---|---|---|
| GET | /sapa | sapa.index |
| POST | /sapa | sapa.store |
| GET | /sapa/create | sapa.create |
| GET | /sapa/export | sapa.export |
| GET | /sapa/{sapa} | sapa.show |
| GET | /sapa/{sapa}/edit | sapa.edit |
| PUT\|PATCH | /sapa/{sapa} | sapa.update |
| DELETE | /sapa/{sapa} | sapa.destroy |

### SPAP (tabela `s_pap`)
Controller: `SPapController`

| Method | URI | Name |
|---|---|---|
| GET | /spap | spap.index |
| POST | /spap | spap.store |
| GET | /spap/create | spap.create |
| GET | /spap/export | spap.export |
| GET | /spap/{spap} | spap.show |
| GET | /spap/{spap}/edit | spap.edit |
| PUT\|PATCH | /spap/{spap} | spap.update |
| DELETE | /spap/{spap} | spap.destroy |

### SRUB (tabela `s_rub`)
Controller: `SRubController`

| Method | URI | Name |
|---|---|---|
| GET | /srub | srub.index |
| POST | /srub | srub.store |
| GET | /srub/create | srub.create |
| GET | /srub/{srub} | srub.show |
| GET | /srub/{srub}/edit | srub.edit |
| PUT\|PATCH | /srub/{srub} | srub.update |
| DELETE | /srub/{srub} | srub.destroy |

---

## Sistema / Livewire / Outros

| Method | URI | Name | Descrição |
|---|---|---|---|
| GET | / | — | redirect para /dashboard |
| POST | /_boost/browser-logs | boost.browser-logs | Laravel Boost (dev) |
| GET | /livewire/livewire.js | — | asset Livewire |
| GET | /livewire/livewire.min.js.map | — | sourcemap Livewire |
| GET | /livewire/preview-file/{filename} | livewire.preview-file | preview upload |
| POST | /livewire/update | livewire.update | requests Livewire |
| POST | /livewire/upload-file | livewire.upload-file | upload Livewire |
| GET | /sanctum/csrf-cookie | sanctum.csrf-cookie | CSRF para SPA |
| GET | /storage/{path} | storage.local | arquivos storage |
| GET | /up | — | health check |
