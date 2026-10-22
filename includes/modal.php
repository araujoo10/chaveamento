<?php
/**
 * Componente Global de Modais
 * Substitui alertas nativos (alert, confirm) e fornece formulários interativos
 */
?>
<!-- 1. Modal Genérico / Confirmação -->
<div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div id="modal-card" class="bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform scale-95 transition-transform duration-200">
        <!-- Cabeçalho -->
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div id="modal-icon-container" class="w-9 h-9 rounded-xl bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center">
                    <i id="modal-icon" class="fa-solid fa-circle-info"></i>
                </div>
                <h3 id="modal-title" class="text-base font-bold text-white">Título do Modal</h3>
            </div>
            <button type="button" onclick="fecharModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Conteúdo -->
        <div class="px-6 py-4 text-sm text-slate-300" id="modal-body">
            Mensagem descritiva do modal.
        </div>

        <!-- Rodapé / Botões -->
        <div class="px-6 py-3 bg-slate-950/60 border-t border-slate-800 flex items-center justify-end space-x-2" id="modal-actions">
            <button type="button" id="modal-btn-cancelar" onclick="fecharModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                Cancelar
            </button>
            <button type="button" id="modal-btn-confirmar" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 transition-colors shadow-lg shadow-brand-500/20">
                Confirmar
            </button>
        </div>
    </div>
</div>

<!-- 2. Modal Especial: Lançar / Editar Placar da Partida -->
<div id="modal-placar" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div class="bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden transform scale-95 transition-transform duration-200">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-gradient-to-r from-slate-900 to-slate-800">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-stopwatch text-sm"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white leading-tight">Lançar Placar da Partida</h3>
                    <p class="text-xs text-slate-400" id="placar-rodada-nome">Fase eliminatória</p>
                </div>
            </div>
            <button type="button" onclick="fecharModalPlacar()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Form de Placar -->
        <form id="form-salvar-placar" onsubmit="enviarPlacarAjax(event)" class="p-6 space-y-6">
            <input type="hidden" name="partida_id" id="placar-partida-id" value="">

            <div class="grid grid-cols-5 items-center gap-3">
                <!-- Time 1 -->
                <div class="col-span-2 text-center p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="w-10 h-10 mx-auto rounded-full bg-brand-600/20 border border-brand-500/30 text-brand-400 flex items-center justify-center font-bold text-sm mb-2" id="placar-avatar1">
                        1
                    </div>
                    <div class="font-bold text-sm text-slate-100 truncate" id="placar-nome1">Equipe A</div>
                    <div class="text-[11px] text-slate-400" id="placar-semente1">Semente #1</div>
                    <div class="mt-3">
                        <label for="input-placar1" class="sr-only">Placar Time 1</label>
                        <input type="number" id="input-placar1" name="placar1" min="0" max="999" required
                            class="w-full text-center text-3xl font-extrabold bg-slate-900 border-2 border-slate-700 focus:border-brand-500 rounded-xl py-2 text-white outline-none transition-all">
                    </div>
                </div>

                <!-- Divisor VS -->
                <div class="col-span-1 text-center font-black text-xl text-slate-600">
                    VS
                </div>

                <!-- Time 2 -->
                <div class="col-span-2 text-center p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="w-10 h-10 mx-auto rounded-full bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center font-bold text-sm mb-2" id="placar-avatar2">
                        2
                    </div>
                    <div class="font-bold text-sm text-slate-100 truncate" id="placar-nome2">Equipe B</div>
                    <div class="text-[11px] text-slate-400" id="placar-semente2">Semente #2</div>
                    <div class="mt-3">
                        <label for="input-placar2" class="sr-only">Placar Time 2</label>
                        <input type="number" id="input-placar2" name="placar2" min="0" max="999" required
                            class="w-full text-center text-3xl font-extrabold bg-slate-900 border-2 border-slate-700 focus:border-brand-500 rounded-xl py-2 text-white outline-none transition-all">
                    </div>
                </div>
            </div>

            <!-- Seção de Pênaltis / Desempate (mostra se empate) -->
            <div id="secao-penaltis" class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-2 hidden">
                <div class="flex items-center text-xs font-semibold text-amber-300">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                    Partida empatada! Defina o resultado dos Pênaltis / Desempate:
                </div>
                <div class="grid grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="text-[11px] text-slate-300 block mb-1">Pênaltis <span id="pen-nome1">Time 1</span></label>
                        <input type="number" id="input-penaltis1" name="penaltis1" min="0" placeholder="0"
                            class="w-full text-center text-lg font-bold bg-slate-900 border border-amber-500/40 focus:border-amber-400 rounded-lg py-1.5 text-white outline-none">
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-300 block mb-1">Pênaltis <span id="pen-nome2">Time 2</span></label>
                        <input type="number" id="input-penaltis2" name="penaltis2" min="0" placeholder="0"
                            class="w-full text-center text-lg font-bold bg-slate-900 border border-amber-500/40 focus:border-amber-400 rounded-lg py-1.5 text-white outline-none">
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-400 text-center flex items-center justify-center">
                <i class="fa-solid fa-shield-halved text-brand-400 mr-1.5"></i>
                O vencedor avançará automaticamente para a próxima fase. O registro é auditado.
            </p>

            <!-- Ações -->
            <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-800">
                <button type="button" onclick="fecharModalPlacar()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btn-salvar-placar-submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 shadow-lg shadow-brand-500/25 transition-all flex items-center">
                    <i class="fa-solid fa-check mr-2"></i> Salvar & Classificar
                </button>
            </div>
        </form>
    </div>
</div>
