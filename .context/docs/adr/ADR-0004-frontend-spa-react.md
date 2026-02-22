# ADR 0004: Frontend em React SPA

## Status
Aceito

## Contexto
O MVP exige uma UI interativa que manipule diversos dados (DataGrids), focada em computadores Desktop (Dashboards e processamento administrativo). A performance server-side (SSR) de SEO não é um requisito válido já que será uma intranet/extranet autenticada.

## Decisão
O Frontend será construído como uma Single Page Application (SPA) utilizando React, focando em "Desktop-first". Sem Server-Side Rendering (SSR) no MVP visando simplificar a arquitetura inicial. O principal componente da listagem será um DataGrid otimizado gerindo o estado complexo de filtros persistentemente e integrando via requisição Fetch/Axios.

## Consequências
- Desenvolvimento e implantação facilitada pois o frontend será gerado de forma puramente estática.
- Troca de fluidez SEO/First Contentful Paint por um fluxo de navegação local mais natural após o carregamento inicial.
- O frontend deverá gerir cuidadosamente caches e persistências locais de filtros (ex: Zustand, LocalStorage ou querystrings).
