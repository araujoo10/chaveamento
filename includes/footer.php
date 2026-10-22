<?php
/**
 * Rodapé Global da Aplicação
 */
require_once __DIR__ . '/../config/bd.php';
$driverName = Database::getDriver();
?>
    </main>

    <!-- Rodapé -->
    <footer class="mt-auto border-t border-slate-800/80 bg-slate-950 py-6 text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 rounded-lg bg-brand-600/20 text-brand-400 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <span class="font-semibold text-slate-300"><?= APP_NAME ?></span> &copy; <?= date('Y') ?> — Organização e Chaveamento Automático
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $driverName === 'mysql' ? 'bg-emerald-400' : 'bg-sky-400' ?>"></span>
                    PDO: <?= strtoupper($driverName) ?>
                </span>
                <span class="text-slate-500">|</span>
                <span class="text-slate-400">Segurança Bcrypt & Auditoria Ativa</span>
            </div>
        </div>
    </footer>

    <!-- Toasts e Modais -->
    <?php require_once __DIR__ . '/toast.php'; ?>
    <?php require_once __DIR__ . '/modal.php'; ?>

    <!-- Scripts JavaScript Globais -->
    <script src="assets/js/main.js"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
