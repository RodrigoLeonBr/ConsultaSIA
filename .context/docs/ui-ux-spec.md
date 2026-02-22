# Especificações de Interface (UI) e Experiência do Usuário (UX)

## Identidade Visual
O V3 prioriza "Data-Heavy UI" focado no uso profissional de retaguarda (Backoffice/Desktop-first), sem necessidade de SSR (Single Page Application para fluidez).

## Layout Base App
- **Topbar**:
  - Exibe Competência Ativa (ex. "Jul/2026").
  - Informações do Operador conectado.
  - *Badges de Auditabilidade*: Mostrando a data/versão da última sincronização ou dump validado das tabelas origem (ex. "Dados base em 20/02/2026").
- **Sidebar**: Menus navegáveis (Relatórios Síncronos, Processing Jobs, Dashboards).

## Regras Obrigatórias para DataGrids
Como o volume de dados do `producao.sql` pode alcançar milhões de linhas (`s_pap` tem +2M):
- **Paginação Server-Side Estrita**: O Grid nunca pede `GET /data` vazio de parâmetros de paginação. O default é enviar `?page=1&limit=50`.
- **Limites Físicos**: O dropdown de tamanho de página na UI deve permitir `50 | 100 | 200` opções. O limite global é `500` e deve ser imposto no motor backend.

## Filtros ("Data-Heavy Rules")
Para não sobrecarregar com excesso de requests ou travamentos locais:
- **Botão Aplicar**: Os filtros são alterados no formulário, mas o DataGrid SÓ DEVE RECARREGAR (disparar Request) após o clique explícito do usuário num botão "Aplicar Filtros". Nada de request a cada tecla digitada (Type-ahead search trigger off).
- **Debounce**: Para raros comboboxes auto-completáveis associados à busca server-side remota (ex.: Busca por nome do prestador), debouncing mínimo de **700ms**.
- **AbortController**: Todo o DataGrid deve cancelar requisições Axios/Fetch em andamento sempre que o usuário descartar ou modificar ativamente uma seleção ou mudar de página repentinamente.

## Relatórios Pesados (Motor Assíncrono)
Se a solicitação transborda limites operacionais síncronos, o fluxo é:
1. Formulário emite um `POST /reports/jobs`. Recebe o Job ID (Status = 202 Accepted).
2. Interface migra para ou exibe uma tela de *Status/Fila*.
3. Worker de front-end inicia um `polling` (ex. a cada 3 a 5 segundos) no `GET /reports/jobs/:id`.
4. Uma vez que o payload retorne `status: 'completed'`:
   - A UI injeta o ID de Resultado e direciona a visualização para a tela do DataGrid padrão usando a URL: `GET /reports/results/:resultId`.
5. Se o fallback de polling falhar repetidamente ou retornar `status: 'failed'`, a UI entra em **Estado de Erro** e exibe o `error_message` correspondente sem quebrar a camada superior.

## Estados da Interface UI
- **Loading State**: Esqueletos (Skeleton) ao longo das linhas do grid. Desativação temporária dos inputs de filtros enquanto carrega.
- **Empty State**: Ilustração minimalista + Texto: "Nenhum resultado de conciliação para os filtros atuais."
- **Error State**: Box em destaque (cores quentes/salmão) contendo a String do erro da API + Botão "Tentar novamente" que acione a Promise subjacente.
