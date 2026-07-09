# Padrão de Exportações — ConsultaProd

> Ver também: `legacy-relatorios-spec.md` para contexto dos campos exportados por relatório.

---

## Classes de Export (`app/Exports/`)

| Classe | Arquivo | Relatório | Tipo |
|---|---|---|---|
| `RelatorioExport` | `RelatorioExport.php` | SIA dinâmico | Lista/tabela |
| `RelatorioApacExport` | `RelatorioApacExport.php` | APAC | Lista/tabela |
| `MatrixReportExport` | `MatrixReportExport.php` | SIA / BPI | Matriz pivot |
| `MatrixReportByPrestadorExport` | `MatrixReportByPrestadorExport.php` | APAC por prestador | Matriz pivot |

---

## Support — Helpers (`app/Support/`)

### `BrazilianNumberFormatter`
**Arquivo**: `app/Support/BrazilianNumberFormatter.php`

Classe central para formatação numérica no padrão brasileiro:

- `BrazilianNumberFormatter::currency($value)` → `"R$ 1.234,56"`
- `BrazilianNumberFormatter::number($value, $decimals)` → `"1.234,56"`
- `BrazilianNumberFormatter::parseForExcel($value)` → valor numérico puro para células Excel
- `BrazilianNumberFormatter::columnFormatsForHeaders($headings)` → mapa de formatos por coluna (inteiro, decimal, moeda)

**Quando usar**: Formatar valores em PHP (CSV, views) e definir formatos de coluna em exports Excel.
**Não usar**: Em queries SQL — formatar apenas na camada de apresentação.

**Exemplo em classe Export:**
```php
use App\Support\BrazilianNumberFormatter;
use Maatwebsite\Excel\Concerns\WithColumnFormats;

class NovoExport implements FromCollection, WithHeadings, WithColumnFormats
{
    public function columnFormats(): array
    {
        return BrazilianNumberFormatter::columnFormatsForHeaders($this->headings());
    }
}
```

---

## Fluxo de Export por Formato

| Formato | Biblioteca | Como é chamado |
|---|---|---|
| Excel (.xlsx) | `Maatwebsite\Excel` | `Excel::download(new XyzExport($dados), 'arquivo.xlsx')` |
| PDF | `Barryvdh\DomPDF` | `Pdf::loadView('relatorios.xyz-pdf', $dados)->download()` |
| CSV | `Maatwebsite\Excel` | `Excel::download(new XyzExport($dados), 'arquivo.csv', \Maatwebsite\Excel\Excel::CSV)` |

---

## Interfaces Maatwebsite Usadas no Projeto

```php
// Exportações com coleção em memória (datasets menores)
implements FromCollection, WithHeadings, WithStyles, WithColumnWidths

// Exportações com query (datasets grandes, lazy loading)
implements FromQuery, WithHeadings, WithStyles, ShouldAutoSize

// Matriz pivot (múltiplas sheets ou colunas dinâmicas)
implements WithMultipleSheets
// ou
implements FromArray, WithHeadings, WithStyles
```

---

## Como Adicionar Nova Exportação

1. Criar `app/Exports/NovoExport.php`
2. Usar `BrazilianNumberFormatter::parseForExcel()` nos valores e `columnFormatsForHeaders()` em `columnFormats()`
3. Usar `BrazilianNumberFormatter::currency()` / `number()` para valores formatados em PHP (PDF, CSV)
4. Registrar no controller correspondente:

```php
use App\Exports\NovoExport;
use Maatwebsite\Excel\Facades\Excel;

public function exportar(Request $request)
{
    $dados = // ... busca dados ...
    return Excel::download(new NovoExport($dados), 'relatorio.xlsx');
}
```

5. Adicionar rota em `routes/web.php` se necessário (ver `routes-map.md`)

---

## Limites Recomendados

| Formato | Linhas máx recomendadas | Observação |
|---|---|---|
| Excel (.xlsx) | 100.000 | PHP memory limit pode ser atingido |
| PDF | 5.000 | DomPDF lento com muitas linhas |
| CSV | sem limite prático | streaming via FromQuery |

> Para volumes maiores, considerar job assíncrono via `report_job` (ver `architecture.md`).

---

## Arquivos de View para PDF

As views de PDF ficam em `resources/views/relatorios/` com sufixo `-pdf.blade.php`:

| View | Relatório |
|---|---|
| `faturamento-prestador-pdf.blade.php` | Faturamento por Prestador |
| (outros conforme relatório) | ... |
