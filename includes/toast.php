<?php
/**
 * Componente Global de Toasts (Notificações Flutuantes)
 * Trata erros e sucessos de forma limpa e moderna
 */
$toastsIniciais = obterToasts();
?>
<!-- Container Fixo de Toasts -->
<div id="toast-container" class="fixed bottom-4 right-4 sm:top-20 sm:bottom-auto z-50 flex flex-col space-y-3 pointer-events-none max-w-sm w-full px-4 sm:px-0">
    <!-- Toasts gerados via PHP -->
    <?php foreach ($toastsIniciais as $toast): ?>
        <div class="toast-item pointer-events-auto bg-slate-800 border shadow-2xl rounded-xl p-4 flex items-start space-x-3 transition-all duration-300 transform translate-y-2 opacity-0
            <?= $toast['tipo'] === 'success' ? 'border-emerald-500/40 text-emerald-400' : '' ?>
            <?= $toast['tipo'] === 'error' ? 'border-rose-500/40 text-rose-400' : '' ?>
            <?= $toast['tipo'] === 'warning' ? 'border-amber-500/40 text-amber-400' : '' ?>
            <?= $toast['tipo'] === 'info' ? 'border-sky-500/40 text-sky-400' : '' ?>
        " data-auto-dismiss="4500">
            <div class="text-xl flex-shrink-0 mt-0.5">
                <?php if ($toast['tipo'] === 'success'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php elseif ($toast['tipo'] === 'error'): ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php elseif ($toast['tipo'] === 'warning'): ?>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-info"></i>
                <?php endif; ?>
            </div>
            <div class="flex-1 text-slate-200">
                <h4 class="font-semibold text-sm leading-tight text-white"><?= htmlspecialchars($toast['titulo']) ?></h4>
                <p class="text-xs text-slate-300 mt-1"><?= htmlspecialchars($toast['mensagem']) ?></p>
            </div>
            <button type="button" onclick="fecharToast(this.closest('.toast-item'))" class="text-slate-400 hover:text-white transition-colors p-1">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>
