@extends('layouts.modern')

@section('title', 'Processando Importação')

@section('header')
<div>
    <h1 class="text-3xl font-bold text-gray-900">Processando importação</h1>
    <p class="text-gray-600 mt-1">
        {{ strtoupper($job['modalidade']) }} — {{ number_format($job['total_rows'], 0, ',', '.') }} linhas em blocos de {{ number_format($chunkSize, 0, ',', '.') }}
    </p>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-8">
                <div id="status-message" class="mb-6 text-sm text-gray-600">
                    Preparando primeiro bloco...
                </div>

                <div class="mb-2 flex justify-between text-sm text-gray-700">
                    <span>Progresso</span>
                    <span id="progress-label">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 mb-6">
                    <div id="progress-bar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center text-sm">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div id="stat-created" class="text-xl font-bold text-green-600">0</div>
                        <div class="text-gray-500">Novos</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div id="stat-changed" class="text-xl font-bold text-amber-600">0</div>
                        <div class="text-gray-500">Alterados</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div id="stat-unchanged" class="text-xl font-bold text-gray-500">0</div>
                        <div class="text-gray-500">Inalterados</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div id="stat-valid" class="text-xl font-bold text-blue-600">0</div>
                        <div class="text-gray-500">Códigos válidos</div>
                    </div>
                </div>

                <div id="error-box" class="hidden mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>

                <p class="mt-6 text-xs text-gray-500">
                    Não feche esta página. O arquivo é lido em até {{ $estimatedChunks }} bloco(s) para evitar timeout.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const chunkUrl = @json(route('sus-paulista.import.process.chunk'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const progressBar = document.getElementById('progress-bar');
        const progressLabel = document.getElementById('progress-label');
        const statusMessage = document.getElementById('status-message');
        const errorBox = document.getElementById('error-box');

        const statCreated = document.getElementById('stat-created');
        const statChanged = document.getElementById('stat-changed');
        const statUnchanged = document.getElementById('stat-unchanged');
        const statValid = document.getElementById('stat-valid');

        function updateProgress(percent) {
            const safe = Math.max(0, Math.min(100, percent));
            progressBar.style.width = safe + '%';
            progressLabel.textContent = safe.toFixed(1) + '%';
        }

        function updateTotals(totals) {
            if (!totals) return;
            statCreated.textContent = totals.created_count ?? 0;
            statChanged.textContent = totals.changed_count ?? 0;
            statUnchanged.textContent = totals.unchanged ?? 0;
            statValid.textContent = totals.valid_rows ?? totals.total_xlsx ?? 0;
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
            statusMessage.textContent = 'Importação interrompida.';
        }

        async function processNextChunk() {
            const response = await fetch(chunkUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Falha ao processar bloco.');
            }

            if (data.progress) {
                updateProgress(data.progress.percent);
                statusMessage.textContent = `Linha ${data.progress.next_row.toLocaleString('pt-BR')} de ${data.progress.total_rows.toLocaleString('pt-BR')}...`;
            }

            if (data.chunk) {
                const chunk = data.chunk;
                statusMessage.textContent += ` Bloco: ${chunk.rows_valid} válido(s), ${chunk.created} novo(s).`;
            }

            updateTotals(data.totals || data.summary);

            if (data.done) {
                updateProgress(100);
                statusMessage.textContent = 'Importação concluída. Redirecionando...';
                window.location.href = data.redirect;
                return;
            }

            await processNextChunk();
        }

        processNextChunk().catch((error) => {
            showError(error.message);
        });
    })();
</script>
@endsection
