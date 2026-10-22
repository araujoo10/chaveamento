<?php
/**
 * Formulário de Criação de Competição e Chaveamento Automático
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/torneios.php';

// Exige login para criar torneios
exigirLogin();

$pageTitle = 'Criar Novo Torneio';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = criarTorneio($_POST);

    if ($resultado['sucesso']) {
        adicionarToast('success', $resultado['mensagem']);
        header('Location: torneio.php?id=' . $resultado['torneio_id']);
        exit;
    } else {
        adicionarToast('error', $resultado['mensagem']);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto my-6">
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-brand-400 font-semibold uppercase tracking-wider mb-1">
                <i class="fa-solid fa-sitemap"></i>
                <span>Assistente de Chaveamento</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Criar Nova Competição</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Configure as regras e cadastre as equipes participantes</p>
        </div>
        <a href="torneios.php" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Voltar
        </a>
    </div>

    <!-- Card Principal do Formulário -->
    <form method="POST" action="novo_torneio.php" id="form-novo-torneio" onsubmit="validarFormulario(event)" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-8">
        
        <!-- Passo 1: Informações Gerais -->
        <div>
            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-800">
                <div class="w-8 h-8 rounded-lg bg-brand-600/20 text-brand-400 flex items-center justify-center font-bold text-sm">
                    1
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Informações da Competição</h2>
                    <p class="text-xs text-slate-400">Defina o nome oficial e a modalidade esportiva</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nome" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Nome do Torneio <span class="text-brand-400">*</span>
                    </label>
                    <input type="text" name="nome" id="nome" required
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        placeholder="Ex: Interclasses de Futsal 2026"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                </div>

                <div>
                    <label for="modalidade" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Modalidade Esportiva <span class="text-brand-400">*</span>
                    </label>
                    <input type="text" name="modalidade" id="modalidade" required
                        value="<?= htmlspecialchars($_POST['modalidade'] ?? '') ?>"
                        placeholder="Ex: Futsal, Vôlei, Futebol, CS2"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                </div>
            </div>

            <!-- Sugestões Rápidas de Modalidades -->
            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] text-slate-400 mr-1">Sugestões:</span>
                <?php
                $modalidades = ['Futsal', 'Futebol Society', 'Vôlei', 'Basquete 3x3', 'Handebol', 'E-Sports (CS2/LoL)', 'Tênis de Mesa', 'Queimada'];
                foreach ($modalidades as $mod):
                ?>
                    <button type="button" onclick="definirModalidade('<?= $mod ?>')" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-brand-600/30 text-slate-300 hover:text-brand-300 border border-slate-700 transition-colors">
                        <?= $mod ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <label for="descricao" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Regulamento / Observações (Opcional)
                </label>
                <textarea name="descricao" id="descricao" rows="2"
                    placeholder="Regras de tempo, prorrogação, local dos jogos..."
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Passo 2: Formato e Regras do Chaveamento -->
        <div>
            <div class="flex items-center space-x-3 mb-4 pb-3 border-b border-slate-800">
                <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-sm">
                    2
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Formato do Chaveamento</h2>
                    <p class="text-xs text-slate-400">Configuração de emparelhamento e disputa de 3º lugar</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Método de Sorteio -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800">
                    <label class="block text-xs font-bold text-slate-200 mb-3">
                        <i class="fa-solid fa-dice text-brand-400 mr-1.5"></i> Método de Confrontos
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-start space-x-3 cursor-pointer p-2 rounded-xl hover:bg-slate-900 transition-colors">
                            <input type="radio" name="tipo_sorteio" value="aleatorio" checked class="mt-1 text-brand-600 focus:ring-brand-500">
                            <div>
                                <span class="text-xs font-semibold text-white block">Sorteio Aleatório (Recomendado)</span>
                                <span class="text-[11px] text-slate-400">Embaralha as equipes para um torneio 100% justo e sem privilégios.</span>
                            </div>
                        </label>
                        <label class="flex items-start space-x-3 cursor-pointer p-2 rounded-xl hover:bg-slate-900 transition-colors">
                            <input type="radio" name="tipo_sorteio" value="semente" class="mt-1 text-brand-600 focus:ring-brand-500">
                            <div>
                                <span class="text-xs font-semibold text-white block">Ordem por Sementes (Cabeças de Chave)</span>
                                <span class="text-[11px] text-slate-400">A ordem digitada na lista determina as sementes (#1 vs #8, #2 vs #7).</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Disputa de 3º Lugar -->
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <label class="block text-xs font-bold text-slate-200 mb-2">
                            <i class="fa-solid fa-award text-amber-400 mr-1.5"></i> Disputa de 3º Lugar
                        </label>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Gera automaticamente uma partida adicional entre os perdedores das duas semifinais para definir o medalhista de bronze.
                        </p>
                    </div>
                    <label class="flex items-center space-x-3 cursor-pointer p-2 rounded-xl bg-slate-900/80 border border-slate-800">
                        <input type="checkbox" name="disputa_3lugar" value="1" checked class="rounded text-brand-600 focus:ring-brand-500">
                        <span class="text-xs font-semibold text-white">Ativar Disputa de 3º Lugar (Bronze)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Passo 3: Participantes e Equipes -->
        <div>
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
                        3
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Equipes / Participantes</h2>
                        <p class="text-xs text-slate-400">Digite um participante por linha (mínimo de 2)</p>
                    </div>
                </div>

                <!-- Botões de Exemplo Rápido -->
                <div class="hidden sm:flex items-center space-x-2">
                    <button type="button" onclick="carregarExemplo(8)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1 text-amber-400"></i> Exemplo 8 Times
                    </button>
                    <button type="button" onclick="carregarExemplo(16)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1 text-indigo-400"></i> Exemplo 16 Times
                    </button>
                    <button type="button" onclick="limparTimes()" class="px-2 py-1.5 rounded-lg text-xs text-slate-500 hover:text-rose-400 transition-colors">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>

            <!-- Botões de Exemplo no Mobile -->
            <div class="sm:hidden flex items-center space-x-2 mb-3">
                <button type="button" onclick="carregarExemplo(8)" class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-slate-800 text-slate-300">
                    8 Times
                </button>
                <button type="button" onclick="carregarExemplo(16)" class="flex-1 py-1.5 rounded-lg text-xs font-medium bg-slate-800 text-slate-300">
                    16 Times
                </button>
                <button type="button" onclick="limparTimes()" class="px-3 py-1.5 rounded-lg text-xs bg-slate-800 text-rose-400">
                    Limpar
                </button>
            </div>

            <div>
                <textarea name="participantes" id="participantes" rows="8" required
                    placeholder="1º ADS A&#10;2º ADS B&#10;3º ADS C&#10;1º Logística&#10;2º Logística&#10;1º Recursos Humanos&#10;2º Recursos Humanos&#10;3º Recursos Humanos"
                    class="w-full font-mono text-sm px-4 py-3 bg-slate-950 border border-slate-700 rounded-2xl text-slate-100 placeholder-slate-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors leading-relaxed"><?= htmlspecialchars($_POST['participantes'] ?? '') ?></textarea>
            </div>

            <!-- Status Dinâmico de Times Digitados -->
            <div class="mt-3 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs text-slate-400 gap-2">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-500/10 text-brand-300 border border-brand-500/20" id="badge-contador">
                        0 Equipes
                    </span>
                    <span id="info-chaveamento">Digite os nomes das equipes participantes.</span>
                </div>
                <div class="text-[11px] text-slate-400">
                    <i class="fa-solid fa-circle-info text-sky-400 mr-1"></i> Números ímpares recebem BYE automaticamente.
                </div>
            </div>
        </div>

        <!-- Botões de Ação Final -->
        <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-3">
            <a href="torneios.php" class="w-full sm:w-auto px-5 py-3 rounded-xl text-center text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                Cancelar
            </a>
            <button type="submit" id="btn-submit-criar" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-brand-600 via-indigo-600 to-brand-600 hover:from-brand-500 hover:to-indigo-500 shadow-xl shadow-brand-500/25 transition-all flex items-center justify-center">
                <i class="fa-solid fa-sitemap mr-2 text-base"></i> Gerar Chaveamento Automático
            </button>
        </div>
    </form>
</div>

<script>
function definirModalidade(nome) {
    document.getElementById('modalidade').value = nome;
}

function carregarExemplo(quantidade) {
    const times8 = [
        "1º ADS - Cyber Knights",
        "2º ADS - Binary Warriors",
        "3º ADS - Code Breakers",
        "1º Logística - Falcões",
        "2º Logística - Titãs",
        "1º RH - Império",
        "2º RH - Gladiadores",
        "3º RH - Águias Douradas"
    ];

    const times16 = [
        "1º ADS - Cyber Knights",
        "2º ADS - Binary Warriors",
        "3º ADS - Code Breakers",
        "1º Logística - Falcões",
        "2º Logística - Titãs",
        "3º Logística - Dragões",
        "1º RH - Império",
        "2º RH - Gladiadores",
        "3º RH - Águias Douradas",
        "1º Administração - Fênix",
        "2º Administração - Tubarões",
        "3º Administração - Leões",
        "1º Contabilidade - Alfa",
        "2º Contabilidade - Ômega",
        "1º Eletrotécnica - Volts",
        "2º Eletrotécnica - Spartans"
    ];

    const lista = (quantidade === 16) ? times16 : times8;
    document.getElementById('participantes').value = lista.join("\n");
    if (!document.getElementById('nome').value) {
        document.getElementById('nome').value = `Torneio Interclasses ${quantidade} Equipes`;
    }
    if (!document.getElementById('modalidade').value) {
        document.getElementById('modalidade').value = 'Futsal';
    }
    atualizarContadorTimes();
    mostrarToast('success', `${quantidade} equipes de demonstração carregadas na lista.`);
}

function limparTimes() {
    document.getElementById('participantes').value = '';
    atualizarContadorTimes();
}

function atualizarContadorTimes() {
    const textarea = document.getElementById('participantes');
    const badge = document.getElementById('badge-contador');
    const info = document.getElementById('info-chaveamento');

    const linhas = textarea.value.split("\n")
        .map(l => l.trim())
        .filter(l => l.length > 0);

    const total = linhas.length;
    badge.textContent = `${total} Equipes`;

    if (total === 0) {
        info.textContent = 'Digite os nomes das equipes participantes.';
        badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700';
    } else if (total < 2) {
        info.textContent = 'Mínimo de 2 equipes necessárias para chaveamento.';
        badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-300 border border-rose-500/20';
    } else {
        // Potência de 2 mais próxima
        let p = 1;
        while (p < total) p *= 2;
        const rodadas = Math.log2(p);
        const byes = p - total;

        badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20';
        if (byes === 0) {
            info.textContent = `Chave perfeita de ${p} times (${rodadas} fases eliminatórias).`;
        } else {
            info.textContent = `Chave de ${p} slots (${rodadas} fases). ${byes} equipes receberão isenção (BYE) na 1ª rodada.`;
        }
    }
}

document.getElementById('participantes').addEventListener('input', atualizarContadorTimes);
window.addEventListener('DOMContentLoaded', atualizarContadorTimes);

function validarFormulario(event) {
    const nome = document.getElementById('nome').value.trim();
    const modalidade = document.getElementById('modalidade').value.trim();
    const textarea = document.getElementById('participantes');

    const linhas = textarea.value.split("\n")
        .map(l => l.trim())
        .filter(l => l.length > 0);

    if (linhas.length < 2) {
        event.preventDefault();
        mostrarToast('warning', 'Cadastre no mínimo 2 participantes para gerar a chave da competição.', 'Participantes Insuficientes');
        return false;
    }

    // Checagem de duplicados
    const nomesSet = new Set();
    for (const linha of linhas) {
        const lower = linha.toLowerCase();
        if (nomesSet.has(lower)) {
            event.preventDefault();
            mostrarToast('error', `A equipe "${linha}" está repetida. Cada participante deve possuir nome único.`, 'Equipe Duplicada');
            return false;
        }
        nomesSet.add(lower);
    }

    const btn = document.getElementById('btn-submit-criar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Gerando Chaves...';
    return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
