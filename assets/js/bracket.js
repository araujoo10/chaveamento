/**
 * Motor Interativo de Visualização de Chaves (Bracket)
 * Inspiração: BracketMaker.app
 * Suporte a Pan/Drag, Zoom, Alternância de Visão e Lançamento de Placares
 */

let zoomLevel = 1.0;
let isPanning = false;
let startX = 0;
let scrollLeft = 0;

document.addEventListener('DOMContentLoaded', () => {
    inicializarPanDrag();
    inicializarZoom();
    inicializarMonitoresPlacar();
});

/**
 * Permite arrastar (Pan) o viewport da chave livremente com o mouse
 */
function inicializarPanDrag() {
    const viewport = document.getElementById('bracket-viewport');
    if (!viewport) return;

    viewport.addEventListener('mousedown', (e) => {
        // Ignora cliques diretos em botões de ação ou links
        if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) {
            return;
        }
        isPanning = true;
        startX = e.pageX - viewport.offsetLeft;
        scrollLeft = viewport.scrollLeft;
    });

    viewport.addEventListener('mouseleave', () => {
        isPanning = false;
    });

    viewport.addEventListener('mouseup', () => {
        isPanning = false;
    });

    viewport.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        e.preventDefault();
        const x = e.pageX - viewport.offsetLeft;
        const walk = (x - startX) * 1.5; // Velocidade de arrasto
        viewport.scrollLeft = scrollLeft - walk;
    });
}

/**
 * Controles de Zoom in, Zoom out e Reset
 */
function inicializarZoom() {
    const btnZoomIn = document.getElementById('btn-zoom-in');
    const btnZoomOut = document.getElementById('btn-zoom-out');
    const btnZoomReset = document.getElementById('btn-zoom-reset');
    const tree = document.getElementById('bracket-tree');

    if (!tree) return;

    const aplicarZoom = () => {
        tree.style.transform = `scale(${zoomLevel})`;
        const lbl = document.getElementById('label-zoom');
        if (lbl) lbl.textContent = `${Math.round(zoomLevel * 100)}%`;
    };

    if (btnZoomIn) {
        btnZoomIn.addEventListener('click', () => {
            if (zoomLevel < 1.6) {
                zoomLevel += 0.1;
                aplicarZoom();
            }
        });
    }

    if (btnZoomOut) {
        btnZoomOut.addEventListener('click', () => {
            if (zoomLevel > 0.6) {
                zoomLevel -= 0.1;
                aplicarZoom();
            }
        });
    }

    if (btnZoomReset) {
        btnZoomReset.addEventListener('click', () => {
            zoomLevel = 1.0;
            aplicarZoom();
            const viewport = document.getElementById('bracket-viewport');
            if (viewport) viewport.scrollLeft = 0;
        });
    }
}

/**
 * Alterna entre visão gráfica (Árvore) e visão em Lista / Tabela
 */
function alternarVisao(modo) {
    const visaoArvore = document.getElementById('visao-arvore');
    const visaoLista = document.getElementById('visao-lista');
    const btnArvore = document.getElementById('btn-tab-arvore');
    const btnLista = document.getElementById('btn-tab-lista');

    if (!visaoArvore || !visaoLista) return;

    if (modo === 'arvore') {
        visaoArvore.classList.remove('hidden');
        visaoLista.classList.add('hidden');
        if (btnArvore) {
            btnArvore.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-600 text-white shadow-md shadow-brand-500/20';
        }
        if (btnLista) {
            btnLista.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition-colors';
        }
    } else {
        visaoArvore.classList.add('hidden');
        visaoLista.classList.remove('hidden');
        if (btnArvore) {
            btnArvore.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition-colors';
        }
        if (btnLista) {
            btnLista.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-600 text-white shadow-md shadow-brand-500/20';
        }
    }
}

/**
 * Monitora inputs de placar para exibir campos de pênalti em caso de empate
 */
function inicializarMonitoresPlacar() {
    const p1 = document.getElementById('input-placar1');
    const p2 = document.getElementById('input-placar2');
    const secaoPenaltis = document.getElementById('secao-penaltis');

    if (!p1 || !p2 || !secaoPenaltis) return;

    const verificarEmpate = () => {
        const v1 = p1.value.trim();
        const v2 = p2.value.trim();

        if (v1 !== '' && v2 !== '' && parseInt(v1, 10) === parseInt(v2, 10)) {
            secaoPenaltis.classList.remove('hidden');
        } else {
            secaoPenaltis.classList.add('hidden');
        }
    };

    p1.addEventListener('input', verificarEmpate);
    p2.addEventListener('input', verificarEmpate);
}

/**
 * Abre o Modal de Lançamento de Placar
 */
function abrirModalPlacar(partida) {
    if (!partida || !partida.id) return;

    const modal = document.getElementById('modal-placar');
    if (!modal) return;

    // Popula dados
    document.getElementById('placar-partida-id').value = partida.id;
    document.getElementById('placar-rodada-nome').textContent = `${partida.nome_rodada || 'Partida'} • Jogo #${partida.ordem_partida || partida.id}`;

    document.getElementById('placar-nome1').textContent = partida.time1_nome || 'Aguardando...';
    document.getElementById('placar-nome2').textContent = partida.time2_nome || 'Aguardando...';

    document.getElementById('placar-semente1').textContent = partida.time1_semente ? `Semente #${partida.time1_semente}` : 'Classificado';
    document.getElementById('placar-semente2').textContent = partida.time2_semente ? `Semente #${partida.time2_semente}` : 'Classificado';

    document.getElementById('pen-nome1').textContent = partida.time1_nome || 'Time 1';
    document.getElementById('pen-nome2').textContent = partida.time2_nome || 'Time 2';

    // Placares anteriores
    document.getElementById('input-placar1').value = (partida.placar1 !== null && partida.placar1 !== undefined) ? partida.placar1 : '';
    document.getElementById('input-placar2').value = (partida.placar2 !== null && partida.placar2 !== undefined) ? partida.placar2 : '';

    document.getElementById('input-penaltis1').value = (partida.penaltis1 !== null && partida.penaltis1 !== undefined) ? partida.penaltis1 : '';
    document.getElementById('input-penaltis2').value = (partida.penaltis2 !== null && partida.penaltis2 !== undefined) ? partida.penaltis2 : '';

    // Dispara checagem de empate
    const secaoPenaltis = document.getElementById('secao-penaltis');
    if (partida.placar1 !== null && partida.placar1 === partida.placar2) {
        secaoPenaltis.classList.remove('hidden');
    } else {
        secaoPenaltis.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
        modal.querySelector('.transform').classList.add('scale-100');
        document.getElementById('input-placar1').focus();
    }, 20);
}

/**
 * Fecha o Modal de Placar
 */
function fecharModalPlacar() {
    const modal = document.getElementById('modal-placar');
    if (!modal) return;

    modal.classList.add('opacity-0');
    const card = modal.querySelector('.transform');
    if (card) {
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

/**
 * Envia o placar via requisição assíncrona AJAX
 */
async function enviarPlacarAjax(event) {
    event.preventDefault();

    const form = document.getElementById('form-salvar-placar');
    const btnSubmit = document.getElementById('btn-salvar-placar-submit');
    const formData = new FormData(form);

    const p1 = parseInt(formData.get('placar1'), 10);
    const p2 = parseInt(formData.get('placar2'), 10);

    if (isNaN(p1) || isNaN(p2) || p1 < 0 || p2 < 0) {
        mostrarToast('warning', 'Informe placares válidos maiores ou iguais a 0.');
        return;
    }

    if (p1 === p2) {
        const pen1 = parseInt(formData.get('penaltis1'), 10);
        const pen2 = parseInt(formData.get('penaltis2'), 10);

        if (isNaN(pen1) || isNaN(pen2) || pen1 === pen2) {
            mostrarToast('warning', 'Em caso de empate no tempo normal, informe os pênaltis com um vencedor definido.', 'Desempate Obrigatório');
            return;
        }
    }

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Salvando...';

    try {
        const response = await fetch('salvar_placar.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.sucesso) {
            mostrarToast('success', data.mensagem || 'Placar registrado com sucesso!');
            fecharModalPlacar();

            // Recarrega a página suavemente após breve delay para que o Toast seja lido
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            mostrarToast('error', data.mensagem || 'Erro ao salvar placar.', 'Falha na Validação');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Salvar & Classificar';
        }
    } catch (err) {
        mostrarToast('error', 'Ocorreu uma falha de conexão com o servidor. Verifique sua rede.', 'Erro de Conexão');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Salvar & Classificar';
    }
}
