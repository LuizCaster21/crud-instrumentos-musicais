import { api } from './services/api.js';
import { auth } from './services/auth.js';
import { showToast } from './utils/toast.js';
import { createIcons, icons } from 'lucide';

// Estado global da aplicação
const state = {
  currentTab: 'instrumentos',
  items: [],
  allCounts: { instrumentos: 0, amplificadores: 0, pedais: 0 },
  deleteTargetId: null,
  searchQuery: '',
};

function renderIcons() {
  createIcons({ icons });
}

function formatBRL(value) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
}

// -------------------------------------------------------------
// INICIALIZAÇÃO DA SESSÃO / TELA
// -------------------------------------------------------------
async function initApp() {
  if (auth.isAuthenticated()) {
    const user = await auth.getMe() || auth.getUser();
    if (user) {
      showDashboard(user);
      return;
    }
  }
  showAuthScreen();
}

function showAuthScreen() {
  document.getElementById('auth-screen').classList.remove('hidden');
  document.getElementById('app-dashboard').classList.add('hidden');
  renderIcons();
}

function showDashboard(user) {
  document.getElementById('auth-screen').classList.add('hidden');
  document.getElementById('app-dashboard').classList.remove('hidden');

  document.getElementById('user-name').textContent = user.nome || 'Usuário';
  document.getElementById('user-email').textContent = user.email || '';
  document.getElementById('user-avatar').textContent = (user.nome || 'U')[0].toUpperCase();

  loadDashboardData();
  renderIcons();
}

// -------------------------------------------------------------
// EVENTOS DE AUTENTICAÇÃO
// -------------------------------------------------------------
const tabLoginBtn = document.getElementById('tab-login-btn');
const tabRegisterBtn = document.getElementById('tab-register-btn');
const formLogin = document.getElementById('form-login');
const formRegister = document.getElementById('form-register');

tabLoginBtn.addEventListener('click', () => {
  tabLoginBtn.className = 'flex-1 py-2 text-sm font-semibold rounded-lg transition-all bg-brand-500 text-white shadow-md';
  tabRegisterBtn.className = 'flex-1 py-2 text-sm font-semibold rounded-lg transition-all text-slate-400 hover:text-white';
  formLogin.classList.remove('hidden');
  formRegister.classList.add('hidden');
});

tabRegisterBtn.addEventListener('click', () => {
  tabRegisterBtn.className = 'flex-1 py-2 text-sm font-semibold rounded-lg transition-all bg-brand-500 text-white shadow-md';
  tabLoginBtn.className = 'flex-1 py-2 text-sm font-semibold rounded-lg transition-all text-slate-400 hover:text-white';
  formRegister.classList.remove('hidden');
  formLogin.classList.add('hidden');
});

formLogin.addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('login-email').value;
  const senha = document.getElementById('login-senha').value;

  try {
    const res = await auth.login(email, senha);
    showToast('Login realizado com sucesso!', 'success');
    showDashboard(res.usuario);
  } catch (err) {
    showToast(err.message || 'Falha ao realizar login.', 'error');
  }
});

formRegister.addEventListener('submit', async (e) => {
  e.preventDefault();
  const nome = document.getElementById('register-nome').value;
  const email = document.getElementById('register-email').value;
  const senha = document.getElementById('register-senha').value;

  try {
    await auth.register(nome, email, senha);
    showToast('Conta criada com sucesso! Realizando login...', 'success');
    const res = await auth.login(email, senha);
    showDashboard(res.usuario);
  } catch (err) {
    showToast(err.message || 'Falha ao cadastrar usuário.', 'error');
  }
});

document.getElementById('btn-logout').addEventListener('click', () => {
  auth.logout();
  showToast('Sessão encerrada.', 'info');
  showAuthScreen();
});

// -------------------------------------------------------------
// CARREGAMENTO DE DADOS DO DASHBOARD
// -------------------------------------------------------------
async function loadDashboardData() {
  try {
    // Busca dados das 3 categorias em paralelo para métricas
    const [resInst, resAmps, resPedais] = await Promise.all([
      api.get('/instrumentos'),
      api.get('/amplificadores'),
      api.get('/pedais')
    ]);

    const instList = resInst.data || [];
    const ampsList = resAmps.data || [];
    const pedaisList = resPedais.data || [];

    state.allCounts.instrumentos = instList.length;
    state.allCounts.amplificadores = ampsList.length;
    state.allCounts.pedais = pedaisList.length;

    // Atualiza contadores nas abas
    document.querySelectorAll('.tab-btn').forEach(btn => {
      const tab = btn.getAttribute('data-tab');
      const badge = btn.querySelector('.tab-badge');
      if (badge) badge.textContent = state.allCounts[tab] || 0;
    });

    // Métricas Financeiras e de Estoque
    const allItems = [...instList, ...ampsList, ...pedaisList];
    const totalEstoque = allItems.reduce((acc, item) => acc + Number(item.quantidade_estoque || 0), 0);
    const totalValor = allItems.reduce((acc, item) => acc + (Number(item.preco || 0) * Number(item.quantidade_estoque || 0)), 0);

    document.getElementById('metric-total-items').textContent = totalEstoque;
    document.getElementById('metric-total-value').textContent = formatBRL(totalValor);
    document.getElementById('metric-count-instrumentos').textContent = instList.length;
    document.getElementById('metric-count-outros').textContent = ampsList.length + pedaisList.length;

    // Carrega a aba ativa
    if (state.currentTab === 'instrumentos') state.items = instList;
    else if (state.currentTab === 'amplificadores') state.items = ampsList;
    else state.items = pedaisList;

    renderItems();

  } catch (err) {
    showToast('Erro ao carregar dados do inventário: ' + err.message, 'error');
  }
}

// -------------------------------------------------------------
// NAVEGAÇÃO ENTRE ABAS (TABS)
// -------------------------------------------------------------
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const tab = btn.getAttribute('data-tab');
    state.currentTab = tab;

    document.querySelectorAll('.tab-btn').forEach(b => {
      const isCurrent = b.getAttribute('data-tab') === tab;
      b.className = isCurrent 
        ? 'tab-btn px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all bg-brand-500 text-white shadow-md'
        : 'tab-btn px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all text-slate-400 hover:text-white hover:bg-dark-800';
    });

    const labels = {
      instrumentos: 'Novo Instrumento',
      amplificadores: 'Novo Amplificador',
      pedais: 'Novo Pedal'
    };
    document.getElementById('btn-add-label').textContent = labels[tab];

    loadDashboardData();
  });
});

// -------------------------------------------------------------
// RENDERIZAÇÃO DOS CARDS
// -------------------------------------------------------------
function renderItems() {
  const container = document.getElementById('items-container');
  const emptyState = document.getElementById('empty-state');

  const filtered = state.items.filter(item => {
    const query = state.searchQuery.toLowerCase();
    const searchString = `${item.nome || ''} ${item.marca || ''} ${item.modelo || ''} ${item.categoria || ''} ${item.tipo || ''} ${item.tipo_efeito || ''}`.toLowerCase();
    return searchString.includes(query);
  });

  if (filtered.length === 0) {
    container.innerHTML = '';
    emptyState.classList.remove('hidden');
    renderIcons();
    return;
  }

  emptyState.classList.add('hidden');
  container.innerHTML = filtered.map(item => createCardHtml(item)).join('');
  renderIcons();
}

function createCardHtml(item) {
  let title = '';
  let subtitle = '';
  let badge1 = '';
  let badge2 = '';

  if (state.currentTab === 'instrumentos') {
    title = item.nome;
    subtitle = 'Instrumento Musical';
    badge1 = `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">${item.categoria}</span>`;
  } else if (state.currentTab === 'amplificadores') {
    title = `${item.marca} ${item.modelo}`;
    subtitle = `${item.potencia_watts}W de Potência`;
    badge1 = `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/10 text-purple-400 border border-purple-500/20">${item.tipo}</span>`;
  } else {
    title = `${item.marca} ${item.modelo}`;
    subtitle = item.tecnologia ? `Tecnologia ${item.tecnologia}` : 'Pedal de Efeito';
    badge1 = `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">${item.tipo_efeito}</span>`;
  }

  const stockBadge = item.quantidade_estoque > 0
    ? `<span class="text-xs font-semibold text-slate-300">Estoque: <strong class="text-white">${item.quantidade_estoque} un</strong></span>`
    : `<span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-red-500/20 text-red-400 border border-red-500/30">Sem Estoque</span>`;

  return `
    <div class="glass-card p-5 rounded-2xl border border-slate-800 flex flex-col justify-between group">
      <div>
        <div class="flex items-start justify-between gap-3 mb-2.5">
          <div class="flex items-center gap-2 flex-wrap">
            ${badge1}
            ${badge2}
          </div>
          <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
            <button onclick="window.editItem(${item.id})" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-400 hover:bg-brand-500/10 transition-colors" title="Editar">
              <i data-lucide="pencil" class="w-4 h-4"></i>
            </button>
            <button onclick="window.confirmDelete(${item.id})" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors" title="Excluir">
              <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <h4 class="font-bold text-base text-white group-hover:text-brand-300 transition-colors">${title}</h4>
        <p class="text-xs text-slate-400 mt-0.5">${subtitle}</p>
      </div>

      <div class="pt-4 mt-4 border-t border-slate-800/80 flex items-center justify-between">
        <div>
          <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Preço Unitário</span>
          <span class="text-lg font-extrabold text-brand-400">${formatBRL(item.preco)}</span>
        </div>
        ${stockBadge}
      </div>
    </div>
  `;
}

// -------------------------------------------------------------
// BUSCA EM TEMPO REAL
// -------------------------------------------------------------
document.getElementById('search-input').addEventListener('input', (e) => {
  state.searchQuery = e.target.value;
  renderItems();
});

// -------------------------------------------------------------
// CONTROLE DO MODAL DE CADASTRO / EDIÇÃO
// -------------------------------------------------------------
const itemModal = document.getElementById('item-modal');
const itemForm = document.getElementById('item-form');
const dynamicFields = document.getElementById('dynamic-form-fields');

function openModal(isEdit = false, item = null) {
  document.getElementById('form-item-id').value = item ? item.id : '';
  document.getElementById('form-preco').value = item ? item.preco : '';
  document.getElementById('form-estoque').value = item ? item.quantidade_estoque : '1';

  const modalTitle = document.getElementById('modal-title');
  const btnSave = document.getElementById('btn-save-label');

  if (state.currentTab === 'instrumentos') {
    modalTitle.textContent = isEdit ? 'Editar Instrumento' : 'Novo Instrumento';
    dynamicFields.innerHTML = `
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Nome do Instrumento</label>
        <input type="text" id="form-nome" required placeholder="Ex: Fender Stratocaster" value="${item?.nome || ''}"
          class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Categoria</label>
        <input type="text" id="form-categoria" required placeholder="Ex: Cordas, Sopro, Teclas, Percussão" value="${item?.categoria || ''}"
          class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
      </div>
    `;
  } else if (state.currentTab === 'amplificadores') {
    modalTitle.textContent = isEdit ? 'Editar Amplificador' : 'Novo Amplificador';
    dynamicFields.innerHTML = `
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Marca</label>
          <input type="text" id="form-marca" required placeholder="Ex: Marshall, Fender" value="${item?.marca || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Modelo</label>
          <input type="text" id="form-modelo" required placeholder="Ex: JCM800, Katana" value="${item?.modelo || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Tipo</label>
          <input type="text" id="form-tipo" required placeholder="Ex: Valvulado, Transistor" value="${item?.tipo || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Potência (Watts)</label>
          <input type="number" id="form-potencia" required placeholder="Ex: 50, 100" value="${item?.potencia_watts || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
      </div>
    `;
  } else {
    modalTitle.textContent = isEdit ? 'Editar Pedal' : 'Novo Pedal de Efeito';
    dynamicFields.innerHTML = `
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Marca</label>
          <input type="text" id="form-marca" required placeholder="Ex: Boss, Strymon" value="${item?.marca || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Modelo</label>
          <input type="text" id="form-modelo" required placeholder="Ex: DS-1, Timeline" value="${item?.modelo || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Tipo de Efeito</label>
          <input type="text" id="form-tipo-efeito" required placeholder="Ex: Distortion, Delay" value="${item?.tipo_efeito || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Tecnologia (Opcional)</label>
          <input type="text" id="form-tecnologia" placeholder="Ex: Analógico, Digital" value="${item?.tecnologia || ''}"
            class="w-full bg-dark-800 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
        </div>
      </div>
    `;
  }

  btnSave.textContent = isEdit ? 'Salvar Alterações' : 'Cadastrar Item';
  itemModal.classList.remove('hidden');
  renderIcons();
}

function closeModal() {
  itemModal.classList.add('hidden');
}

document.getElementById('btn-open-modal').addEventListener('click', () => openModal(false));
document.getElementById('empty-add-btn').addEventListener('click', () => openModal(false));
document.getElementById('btn-close-modal').addEventListener('click', closeModal);
document.getElementById('btn-cancel-modal').addEventListener('click', closeModal);

// ENVIO DO FORMULÁRIO DE CADASTRO / EDIÇÃO
itemForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = document.getElementById('form-item-id').value;
  const preco = parseFloat(document.getElementById('form-preco').value);
  const quantidade_estoque = parseInt(document.getElementById('form-estoque').value, 10);

  let payload = { preco, quantidade_estoque };
  let endpoint = `/${state.currentTab}`;

  if (state.currentTab === 'instrumentos') {
    payload.nome = document.getElementById('form-nome').value;
    payload.categoria = document.getElementById('form-categoria').value;
  } else if (state.currentTab === 'amplificadores') {
    payload.marca = document.getElementById('form-marca').value;
    payload.modelo = document.getElementById('form-modelo').value;
    payload.tipo = document.getElementById('form-tipo').value;
    payload.potencia_watts = parseInt(document.getElementById('form-potencia').value, 10);
  } else {
    payload.marca = document.getElementById('form-marca').value;
    payload.modelo = document.getElementById('form-modelo').value;
    payload.tipo_efeito = document.getElementById('form-tipo-efeito').value;
    payload.tecnologia = document.getElementById('form-tecnologia').value || null;
  }

  try {
    if (id) {
      await api.put(`${endpoint}/${id}`, payload);
      showToast('Equipamento atualizado com sucesso!', 'success');
    } else {
      await api.post(endpoint, payload);
      showToast('Equipamento cadastrado com sucesso!', 'success');
    }
    closeModal();
    loadDashboardData();
  } catch (err) {
    showToast(err.message || 'Erro ao salvar registro.', 'error');
  }
});

// Ações Globais expostas para os botões dos Cards
window.editItem = (id) => {
  const item = state.items.find(i => i.id === id);
  if (item) openModal(true, item);
};

window.confirmDelete = (id) => {
  state.deleteTargetId = id;
  document.getElementById('delete-modal').classList.remove('hidden');
  renderIcons();
};

document.getElementById('btn-cancel-delete').addEventListener('click', () => {
  state.deleteTargetId = null;
  document.getElementById('delete-modal').classList.add('hidden');
});

document.getElementById('btn-confirm-delete').addEventListener('click', async () => {
  if (!state.deleteTargetId) return;

  try {
    await api.delete(`/${state.currentTab}/${state.deleteTargetId}`);
    showToast('Equipamento excluído com sucesso!', 'success');
    document.getElementById('delete-modal').classList.add('hidden');
    state.deleteTargetId = null;
    loadDashboardData();
  } catch (err) {
    showToast(err.message || 'Erro ao excluir equipamento.', 'error');
  }
});

// Inicia a aplicação ao carregar a página
initApp();
