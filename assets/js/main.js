/**
 * Scripts Globais da Aplicação
 * Gerenciamento de Toasts, Modais e Menu Mobile
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Menu Mobile Toggle
    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const iconBars = document.getElementById('menu-icon-bars');
    const iconClose = document.getElementById('menu-icon-close');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                iconBars.classList.add('hidden');
                iconClose.classList.remove('hidden');
            } else {
                mobileMenu.classList.add('hidden');
                iconBars.classList.remove('hidden');
                iconClose.classList.add('hidden');
            }
        });
    }

    // 2. Inicialização dos Toasts PHP existentes
    document.querySelectorAll('.toast-item').forEach(toast => {
        iniciarAutoDismissToast(toast);
    });
});

/**
 * Inicia temporizador de auto-dismiss de um Toast
 */
function iniciarAutoDismissToast(toast) {
    const delay = parseInt(toast.getAttribute('data-auto-dismiss') || '4500', 10);
    setTimeout(() => {
        fecharToast(toast);
    }, delay);
}

/**
 * Fecha um elemento de Toast com transição suave
 */
function fecharToast(toastElement) {
    if (!toastElement) return;
    toastElement.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
        if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
        }
    }, 300);
}

/**
 * Exibe dinamicamente uma notificação Toast na tela
 *
 * @param {string} tipo 'success' | 'error' | 'warning' | 'info'
 * @param {string} mensagem Texto descritivo
 * @param {string} titulo Título opcional
 */
function mostrarToast(tipo, mensagem, titulo = '') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    if (!titulo) {
        titulo = tipo === 'success' ? 'Sucesso' : (tipo === 'error' ? 'Atenção' : (tipo === 'warning' ? 'Aviso' : 'Informação'));
    }

    const toast = document.createElement('div');
    toast.className = `toast-item pointer-events-auto bg-slate-800 border shadow-2xl rounded-xl p-4 flex items-start space-x-3 transition-all duration-300 transform translate-y-2 opacity-0 ${
        tipo === 'success' ? 'border-emerald-500/40 text-emerald-400' :
        tipo === 'error' ? 'border-rose-500/40 text-rose-400' :
        tipo === 'warning' ? 'border-amber-500/40 text-amber-400' :
        'border-sky-500/40 text-sky-400'
    }`;
    toast.setAttribute('data-auto-dismiss', '4500');

    let iconHtml = '<i class="fa-solid fa-circle-info"></i>';
    if (tipo === 'success') iconHtml = '<i class="fa-solid fa-circle-check"></i>';
    if (tipo === 'error') iconHtml = '<i class="fa-solid fa-circle-exclamation"></i>';
    if (tipo === 'warning') iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>';

    toast.innerHTML = `
        <div class="text-xl flex-shrink-0 mt-0.5">
            ${iconHtml}
        </div>
        <div class="flex-1 text-slate-200">
            <h4 class="font-semibold text-sm leading-tight text-white">${escapeHtml(titulo)}</h4>
            <p class="text-xs text-slate-300 mt-1">${escapeHtml(mensagem)}</p>
        </div>
        <button type="button" onclick="fecharToast(this.closest('.toast-item'))" class="text-slate-400 hover:text-white transition-colors p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    `;

    container.appendChild(toast);

    // Trigger de transição
    setTimeout(() => {
        toast.classList.remove('opacity-0', 'translate-y-2');
    }, 20);

    iniciarAutoDismissToast(toast);
}

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

/**
 * Abre o Modal Universal com suporte a callback
 */
function abrirModal({
    titulo = 'Confirmação',
    mensagem = '',
    tipo = 'info', // 'info', 'warning', 'danger', 'success'
    textoConfirmar = 'Confirmar',
    textoCancelar = 'Cancelar',
    aoConfirmar = null,
    esconderCancelar = false
}) {
    const container = document.getElementById('modal-container');
    const card = document.getElementById('modal-card');
    const titleEl = document.getElementById('modal-title');
    const bodyEl = document.getElementById('modal-body');
    const iconContainer = document.getElementById('modal-icon-container');
    const iconEl = document.getElementById('modal-icon');
    const btnConfirmar = document.getElementById('modal-btn-confirmar');
    const btnCancelar = document.getElementById('modal-btn-cancelar');

    if (!container) return;

    titleEl.textContent = titulo;
    bodyEl.innerHTML = mensagem;

    // Estilo dos botões e ícone de acordo com o tipo
    iconContainer.className = 'w-9 h-9 rounded-xl flex items-center justify-center border ';
    btnConfirmar.className = 'px-4 py-2 rounded-xl text-xs font-semibold text-white transition-all shadow-lg ';

    if (tipo === 'danger') {
        iconContainer.classList.add('bg-rose-500/10', 'border-rose-500/20', 'text-rose-400');
        iconEl.className = 'fa-solid fa-triangle-exclamation';
        btnConfirmar.classList.add('bg-rose-600', 'hover:bg-rose-500', 'shadow-rose-500/20');
    } else if (tipo === 'warning') {
        iconContainer.classList.add('bg-amber-500/10', 'border-amber-500/20', 'text-amber-400');
        iconEl.className = 'fa-solid fa-triangle-exclamation';
        btnConfirmar.classList.add('bg-amber-600', 'hover:bg-amber-500', 'shadow-amber-500/20');
    } else if (tipo === 'success') {
        iconContainer.classList.add('bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-400');
        iconEl.className = 'fa-solid fa-circle-check';
        btnConfirmar.classList.add('bg-emerald-600', 'hover:bg-emerald-500', 'shadow-emerald-500/20');
    } else {
        iconContainer.classList.add('bg-brand-500/10', 'border-brand-500/20', 'text-brand-400');
        iconEl.className = 'fa-solid fa-circle-info';
        btnConfirmar.classList.add('bg-brand-600', 'hover:bg-brand-500', 'shadow-brand-500/20');
    }

    btnConfirmar.textContent = textoConfirmar;
    btnCancelar.textContent = textoCancelar;
    btnCancelar.style.display = esconderCancelar ? 'none' : 'inline-block';

    // Handler de confirmação
    btnConfirmar.onclick = () => {
        fecharModal();
        if (typeof aoConfirmar === 'function') {
            aoConfirmar();
        }
    };

    // Abre
    container.classList.remove('hidden');
    setTimeout(() => {
        container.classList.remove('opacity-0');
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 20);
}

/**
 * Fecha o Modal Universal
 */
function fecharModal() {
    const container = document.getElementById('modal-container');
    const card = document.getElementById('modal-card');
    if (!container) return;

    container.classList.add('opacity-0');
    if (card) {
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
    }
    setTimeout(() => {
        container.classList.add('hidden');
    }, 200);
}

/**
 * Exibe confirmação amigável substituindo window.confirm
 */
function confirmarAcao(mensagem, aoConfirmar, titulo = 'Confirmar Ação', tipo = 'warning') {
    abrirModal({
        titulo,
        mensagem,
        tipo,
        textoConfirmar: 'Sim, prosseguir',
        textoCancelar: 'Cancelar',
        aoConfirmar
    });
}
